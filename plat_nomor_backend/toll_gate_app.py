import cv2
import easyocr
from ultralytics import YOLO
import tkinter as tk
from tkinter import filedialog, ttk, font, messagebox
from PIL import Image, ImageTk
from datetime import datetime, timedelta
import threading
import requests
import json
import os
import sys
import time
import math
import random
from tkinter.colorchooser import askcolor
import numpy as np
import urllib.request
import warnings
import difflib
import io
try:
    import pygame  # Untuk memainkan file audio
    pygame.mixer.init()
except ImportError:
    pygame = None
import subprocess  # Untuk suara beep di Linux

# Menekan semua warning
warnings.filterwarnings("ignore")
os.environ["PYTHONWARNINGS"] = "ignore"

# --- FUNGSI KLASIFIKASI KENDARAAN ---
def classify_vehicle(img, vehicle_model):
    """
    Mengklasifikasi jenis kendaraan dari gambar - disesuaikan untuk kendaraan di Indonesia
    """
    results = vehicle_model(img, conf=0.3, verbose=False)
    
    # Kelas kendaraan yang dapat dideteksi oleh model YOLOv8
    # Disesuaikan untuk konteks Indonesia
    vehicle_classes = {
        2: "Mobil",         # car
        5: "Bus",           # bus
        7: "Truk",          # truck - diperbaiki agar truck terdeteksi sebagai Truk
    }
    
    # Untuk kendaraan yang lebih besar seperti truk kontainer, dump truck, dll
    large_truck_keywords = ["kontainer", "dump", "trailer", "tanker", "besar", "gandeng"]
    
    # Default jika tidak ada kendaraan yang terdeteksi
    detected_vehicle = {"class": 0, "name": "Kendaraan Lain", "conf": 0, "box": None}
    
    # List untuk menyimpan semua kendaraan yang terdeteksi
    all_detected_vehicles = []
    
    if len(results[0].boxes) > 0:
        best_conf = 0
        for box in results[0].boxes:
            cls = int(box.cls[0].item())
            conf = float(box.conf[0].item())
            
            # Hanya proses jika kelas adalah kendaraan yang umum di jalan tol
            if cls in vehicle_classes and conf > 0.30:  # Threshold lebih tinggi untuk mengurangi false positive
                coords = box.xyxy[0].cpu().numpy().astype(int)
                x1, y1, x2, y2 = coords
                
                # Tentukan jenis kendaraan
                vehicle_name = vehicle_classes.get(cls)
                
                # Untuk truk (class 7), sudah dikategorikan sebagai Truk
                # Bisa ditambah logika khusus untuk membedakan jenis truk jika diperlukan
                if cls == 7:
                    vehicle_name = "Truk"  # Pastikan semua truck terdeteksi sebagai Truk
                
                vehicle = {
                    "class": cls,
                    "name": vehicle_name,
                    "conf": conf,
                    "box": coords
                }
                
                all_detected_vehicles.append(vehicle)
                
                # Update kendaraan dengan confidence tertinggi
                if conf > best_conf:
                    best_conf = conf
                    detected_vehicle = vehicle
    
    return detected_vehicle, all_detected_vehicles

# --- FUNGSI DETEKSI PLAT NOMOR ---
def detect_plate_yolo_and_ocr(source_img, yolo_model, ocr_reader):
    """
    Mendeteksi plat nomor dari gambar (baik path file maupun frame cv2).
    """
    if isinstance(source_img, str):
        img = cv2.imread(source_img)
    else:
        img = source_img

    if img is None:
        return "Gagal memuat gambar", None, None, 0

    results = yolo_model(img, conf=0.25, verbose=False)
    best_confidence = 0
    best_plate_roi = None
    best_box = None

    for box in results[0].boxes:
        confidence = box.conf[0]
        if confidence > best_confidence:
            best_confidence = float(confidence)
            coords = box.xyxy[0].cpu().numpy().astype(int)
            x1, y1, x2, y2 = coords
            padding = 5
            plate_roi = img[max(0, y1-padding):y2+padding, max(0, x1-padding):x2+padding]
            best_plate_roi = plate_roi
            best_box = (x1, y1, x2, y2)

    if best_plate_roi is not None:
        (x1, y1, x2, y2) = best_box
        cv2.rectangle(img, (x1, y1), (x2, y2), (0, 255, 0), 2)

        ocr_result = ocr_reader.readtext(best_plate_roi, detail=1, paragraph=False)

        plate_number = "Tidak terbaca"

        if ocr_result:
            # Gabungkan semua teks terdeteksi
            raw_plate = " ".join([text[1] for text in ocr_result]).upper()
            # Bersihkan teks
            cleaned_plate = "".join([c for c in raw_plate if c.isalnum() or c == ' '])
            plate_number = " ".join(cleaned_plate.split())

            if plate_number != "Tidak terbaca":
                cv2.putText(img, plate_number, (x1, y1 - 10),
                           cv2.FONT_HERSHEY_SIMPLEX, 1.2, (36, 255, 12), 3)

        return plate_number, img, best_plate_roi, best_confidence

    return "Plat tidak terdeteksi", img, None, 0

# --- APLIKASI GUI DENGAN TKINTER ---
class RoundedButton(tk.Canvas):
    def __init__(self, parent, width, height, corner_radius, padding=0, color="#1089ff", text="", command=None, **kwargs):
        super().__init__(parent, width=width, height=height, highlightthickness=0, **kwargs)
        self.command = command
        self.color = color
        self.hover_color = self.lighten_color(color, 0.2)
        self.pressed_color = self.darken_color(color, 0.2)
        self.current_color = color
        self.corner_radius = corner_radius
        self.state = "normal"
        
        # Draw rounded rectangle
        self.rect_item = self.create_rounded_rect(padding, padding, width-padding, height-padding, 
                                                corner_radius, fill=color, outline="")
        
        # Add text
        font_size = min(height-padding*2-6, 12)
        self.text_item = self.create_text(width/2, height/2, text=text, 
                                        fill="white", font=("Arial", font_size, "bold"))
        
        # Bind events
        self.bind("<Enter>", self.on_enter)
        self.bind("<Leave>", self.on_leave)
        self.bind("<ButtonPress-1>", self.on_press)
        self.bind("<ButtonRelease-1>", self.on_release)
    
    def create_rounded_rect(self, x1, y1, x2, y2, radius, **kwargs):
        points = [
            x1+radius, y1,
            x2-radius, y1,
            x2, y1,
            x2, y1+radius,
            x2, y2-radius,
            x2, y2,
            x2-radius, y2,
            x1+radius, y2,
            x1, y2,
            x1, y2-radius,
            x1, y1+radius,
            x1, y1
        ]
        return self.create_polygon(points, smooth=True, **kwargs)
    
    def on_enter(self, event):
        if self.state == "normal":
            self.itemconfig(self.rect_item, fill=self.hover_color)
            self.current_color = self.hover_color
    
    def on_leave(self, event):
        if self.state == "normal":
            self.itemconfig(self.rect_item, fill=self.color)
            self.current_color = self.color
    
    def on_press(self, event):
        if self.state == "normal":
            self.itemconfig(self.rect_item, fill=self.pressed_color)
            self.current_color = self.pressed_color
    
    def on_release(self, event):
        if self.state == "normal":
            self.itemconfig(self.rect_item, fill=self.hover_color)
            self.current_color = self.hover_color
            if self.command:
                self.command()
    
    def set_state(self, state):
        self.state = state
        if state == "disabled":
            self.itemconfig(self.rect_item, fill="#cccccc")
            self.current_color = "#cccccc"
        else:
            self.itemconfig(self.rect_item, fill=self.color)
            self.current_color = self.color
    
    def lighten_color(self, color, amount=0.2):
        # Convert hex to RGB
        color = color.lstrip('#')
        r, g, b = tuple(int(color[i:i+2], 16) for i in (0, 2, 4))
        
        # Lighten
        r = min(255, int(r + (255 - r) * amount))
        g = min(255, int(g + (255 - g) * amount))
        b = min(255, int(b + (255 - b) * amount))
        
        # Convert back to hex
        return f'#{r:02x}{g:02x}{b:02x}'
    
    def darken_color(self, color, amount=0.2):
        # Convert hex to RGB
        color = color.lstrip('#')
        r, g, b = tuple(int(color[i:i+2], 16) for i in (0, 2, 4))
        
        # Darken
        r = max(0, int(r * (1 - amount)))
        g = max(0, int(g * (1 - amount)))
        b = max(0, int(b * (1 - amount)))
        
        # Convert back to hex
        return f'#{r:02x}{g:02x}{b:02x}'

class ParticleAnimation:
    def __init__(self, canvas, width, height, num_particles=30, color="#ffffff"):
        self.canvas = canvas
        self.width = width
        self.height = height
        self.num_particles = num_particles
        self.color = color
        self.particles = []
        self.running = False
        
        # Create particles
        for _ in range(num_particles):
            x = random.randint(0, width)
            y = random.randint(0, height)
            size = random.randint(2, 5)
            speed_x = random.uniform(-1, 1)
            speed_y = random.uniform(-1, 1)
            
            particle = {
                'id': self.canvas.create_oval(x, y, x+size, y+size, fill=color, outline=""),
                'x': x,
                'y': y,
                'size': size,
                'speed_x': speed_x,
                'speed_y': speed_y
            }
            self.particles.append(particle)
    
    def start(self):
        self.running = True
        self.animate()
    
    def stop(self):
        self.running = False
    
    def animate(self):
        if not self.running:
            return
        
        for p in self.particles:
            # Move particle
            p['x'] += p['speed_x']
            p['y'] += p['speed_y']
            
            # Wrap around edges
            if p['x'] < 0:
                p['x'] = self.width
            elif p['x'] > self.width:
                p['x'] = 0
                
            if p['y'] < 0:
                p['y'] = self.height
            elif p['y'] > self.height:
                p['y'] = 0
            
            # Update canvas
            self.canvas.coords(p['id'], p['x'], p['y'], p['x']+p['size'], p['y']+p['size'])
        
        # Schedule next animation frame
        if self.running:
            self.canvas.after(50, self.animate)

class TollGateApp:
    def __init__(self, root):
        self.root = root
        self.root.title("EZToll Gate System - Automatic License Plate Recognition")
        # Maximize window - cross platform
        try:
            self.root.state('zoomed')  # Windows
        except:
            self.root.attributes('-zoomed', True)  # Linux
            # Tambahkan metode alternatif untuk memaksimalkan pada Linux
            self.root.attributes('-fullscreen', True)
            # Tambahkan binding untuk keluar dari fullscreen dengan Escape
            self.root.bind('<Escape>', lambda event: self.toggle_fullscreen())
        self.root.configure(bg="#0a1128")
        self.root.protocol("WM_DELETE_WINDOW", self.on_closing)

        # API URL - menggunakan API baru (localhost untuk testing)
        self.api_url = "http://127.0.0.1:8080/api"
        
        # Current user data from API
        self.current_user_data = None
        
        # Initialize tarifs list
        self.tarifs = []
        
        
        # Load license plate detection model dengan konfigurasi untuk menghindari warning
        self.yolo_model = YOLO('license_plate_detector.pt')
        self.ocr_reader = easyocr.Reader(['id'], gpu=False)
        
        # Warning sudah ditekan di awal file
        
        # Load vehicle classification model - menggunakan YOLOv8 default
        self.vehicle_model_path = 'yolov8n.pt'
        if not os.path.exists(self.vehicle_model_path):
            print("Mendownload model klasifikasi kendaraan...")
            urllib.request.urlretrieve('https://github.com/ultralytics/assets/releases/download/v0.0.0/yolov8n.pt', 
                                      self.vehicle_model_path)
        
        print("🚗 Loading YOLOv8 vehicle classification model...")
        self.vehicle_model = YOLO(self.vehicle_model_path)
        
        # Mapping dari tipe kendaraan ke jenis kendaraan tarif
        self.vehicle_to_tarif = {
            "Mobil": "Mobil",
            "Bus": "Bus",
            "Truk": "Truk"
        }
        
        # Tambahan informasi untuk klasifikasi kendaraan Indonesia
        self.indonesia_vehicles = {
            "minibus": "Mobil",    # Avanza, Xenia, Innova, dll
            "pickup": "Mobil",     # Hilux, Triton, dll
            "van": "Mobil",        # L300, Carry, Grandmax, dll
            "suv": "Mobil",        # Fortuner, Pajero, CR-V, dll
            "mpv": "Mobil",        # Avanza, Xenia, Innova, dll
            "sedan": "Mobil",      # Civic, Corolla, dll
            "hatchback": "Mobil",  # Jazz, Yaris, dll
            "bus": "Bus",          # Bus kota, bus antar kota, dll
            "truk_besar": "Truk",  # Truk kontainer, dump truck, dll
            "truk_sedang": "Truk"  # Colt diesel, truk kecil, dll
        }
        
        # App state variables
        self.processing_active = False
        self.video_capture = None
        self.last_detected_plate = ""
        self.last_transaction_time = 0
        self.dark_mode = True  # Default to dark mode
        self.animation_active = True  # Animation state
        self.detected_vehicle = {"class": 0, "name": "Kendaraan Lain", "conf": 0, "box": None}
        self.auto_select_tarif = True  # Auto select tarif based on vehicle classification
        self.processed_transactions = {}  # Dictionary untuk menyimpan transaksi yang sudah diproses (plate+vehicle)
        self.gate_open = False  # Status palang pintu (tertutup/terbuka)
        self.gate_open_time = 0  # Waktu palang terbuka
        self.gate_open_duration = 10  # Durasi palang terbuka dalam detik
        
        # Tracking untuk multiple kendaraan dalam satu frame
        self.detected_vehicles = {}  # Dictionary untuk menyimpan kendaraan yang terdeteksi (id: data)
        self.next_vehicle_id = 1  # ID unik untuk kendaraan berikutnya
        
        # Video processing variables
        self.frame_skip = 2  # Default: proses setiap 3 frame (skip 2 frame)
        self.frame_count = 0  # Counter untuk frame skipping
        self.adaptive_skipping = True  # Enable adaptive frame skipping
        self.processing_times = []  # Untuk menghitung rata-rata waktu pemrosesan
        self.last_frame_time = 0  # Untuk menghitung FPS
        self.current_fps = 0  # FPS saat ini
        self.target_fps = 15  # Target FPS untuk adaptive skipping
        
        # Variabel untuk mengelola plat yang sudah diproses
        self.plate_similarity_threshold = 0.8  # Ambang kemiripan plat untuk dianggap sama
        
        # Statistics
        self.stats = {
            "total_detections": 0,
            "successful_transactions": 0,
            "failed_transactions": 0,
            "unregistered_plates": 0,
            "vehicle_types": {"Mobil": 0, "Bus": 0, "Truk": 0},
            "start_time": datetime.now()
        }

        self.init_fonts_and_styles()
        self.create_widgets()
        self.update_clock()
        
        # Load tarifs from API
        self.load_tarifs()
        
        # Start background animation
        self.start_background_animation()

    def init_fonts_and_styles(self):
        self.title_font = font.Font(family="Montserrat", size=20, weight="bold")
        self.subtitle_font = font.Font(family="Montserrat", size=16, weight="bold")
        self.label_font = font.Font(family="Roboto", size=12)
        self.result_font = font.Font(family="Roboto Mono", size=32, weight="bold")
        self.button_font = font.Font(family="Roboto", size=12, weight="bold")
        self.status_font = font.Font(family="Roboto", size=10)
        
        # Tema warna
        self.colors = {
            "bg_dark": "#0a1128",
            "bg_medium": "#001f54",
            "bg_light": "#034078",
            "accent": "#1282a2",
            "accent_light": "#0abdc6",
            "success": "#2ecc71",
            "warning": "#f39c12",
            "error": "#e74c3c",
            "text_light": "#fefcfb",
            "text_dark": "#333333"
        }
        
        # Konfigurasi style untuk widget ttk
        style = ttk.Style()
        style.theme_use("clam")
        
        # Treeview style
        style.configure("Treeview", 
                        background=self.colors["bg_medium"],
                        foreground=self.colors["text_light"],
                        fieldbackground=self.colors["bg_medium"],
                        rowheight=30,
                        font=("Roboto", 10))
        
        style.configure("Treeview.Heading", 
                        background=self.colors["bg_light"],
                        foreground=self.colors["text_light"],
                        font=("Montserrat", 12, "bold"))
        
        # Combobox style
        style.configure("TCombobox", 
                        background=self.colors["bg_light"],
                        foreground=self.colors["text_dark"],
                        fieldbackground=self.colors["text_light"],
                        font=("Roboto", 12))
        
        style.map('Treeview', background=[('selected', self.colors["accent"])])
        
        # Scrollbar style
        style.configure("TScrollbar", 
                        background=self.colors["bg_light"],
                        troughcolor=self.colors["bg_medium"],
                        arrowcolor=self.colors["text_light"])
        
        # Progress bar styles - different colors based on confidence level
        style.configure("green.Horizontal.TProgressbar", 
                       background=self.colors["success"],
                       troughcolor=self.colors["bg_dark"])
        
        style.configure("yellow.Horizontal.TProgressbar", 
                       background=self.colors["warning"],
                       troughcolor=self.colors["bg_dark"])
        
        style.configure("red.Horizontal.TProgressbar", 
                       background=self.colors["error"],
                       troughcolor=self.colors["bg_dark"])

    def create_gradient_frame(self, parent, color1, color2, height=60):
        """Create a frame with gradient background"""
        gradient_frame = tk.Canvas(parent, height=height, highlightthickness=0)
        gradient_frame.pack(fill="x")
        
        # Create gradient
        for i in range(height):
            # Calculate color for this line
            r1, g1, b1 = [int(color1[i:i+2], 16) for i in (1, 3, 5)]
            r2, g2, b2 = [int(color2[i:i+2], 16) for i in (1, 3, 5)]
            
            r = int(r1 + (r2-r1) * i / height)
            g = int(g1 + (g2-g1) * i / height)
            b = int(b1 + (b2-b1) * i / height)
            
            color = f'#{r:02x}{g:02x}{b:02x}'
            gradient_frame.create_line(0, i, 5000, i, fill=color)
            
        return gradient_frame
        
    def open_gate(self):
        """
        Membuka palang pintu tol
        Fungsi ini akan mengirim sinyal ke perangkat IoT untuk membuka palang
        """
        self.gate_open = True
        self.gate_open_time = time.time()
        
        # Kirim sinyal ke perangkat IoT untuk membuka palang
        # Dalam implementasi nyata, ini akan mengirim sinyal ke perangkat IoT
        # Misalnya menggunakan GPIO pada Raspberry Pi atau Arduino
        # GPIO.output(GATE_PIN, GPIO.HIGH)  # Contoh kode untuk Raspberry Pi
        print("PALANG TERBUKA: Mengirim sinyal ke perangkat IoT")
        
        # Tampilkan popup notifikasi palang terbuka
        self.show_gate_notification(True)
        
        # Jadwalkan penutupan palang setelah durasi tertentu
        self.root.after(self.gate_open_duration * 1000, self.close_gate)
    
    def close_gate(self):
        """
        Menutup palang pintu tol
        Fungsi ini akan mengirim sinyal ke perangkat IoT untuk menutup palang
        """
        if self.gate_open:
            self.gate_open = False
            
            # Kirim sinyal ke perangkat IoT untuk menutup palang
            # Dalam contoh ini, kita hanya mensimulasikan dengan print
            print("PALANG TERTUTUP: Mengirim sinyal ke perangkat IoT")
            
            # Update indikator palang
            self.show_gate_indicator(False)
    
    def show_gate_indicator(self, is_open):
        """
        Menampilkan indikator status palang pada UI
        """
        if hasattr(self, 'gate_indicator_label'):
            if is_open:
                self.gate_indicator_label.config(text="🚧 PALANG TERBUKA 🚧", fg=self.colors["success"])
            else:
                self.gate_indicator_label.config(text="🚧 PALANG TERTUTUP 🚧", fg=self.colors["error"])
    
    def show_toast_notification(self, title, message, type="info"):
        """
        Menampilkan notifikasi toast yang muncul dan menghilang otomatis
        """
        # Tentukan warna berdasarkan tipe
        color_map = {
            "info": "#01579b",  # Biru tua
            "success": "#1e8f35",  # Hijau lebih gelap
            "warning": "#e65100",  # Oranye tua
            "error": "#c62828"  # Merah lebih gelap
        }
        bg_color = color_map.get(type, "#01579b")
        
        # Buat window toast tanpa judul dan border
        toast = tk.Toplevel(self.root)
        toast.overrideredirect(True)  # Hilangkan title bar
        toast.attributes("-topmost", True)  # Selalu di atas
        toast.configure(bg=bg_color)
        
        # Buat frame dengan border dan efek bayangan
        outer_frame = tk.Frame(toast, bg=self.colors["bg_dark"], padx=2, pady=2)
        outer_frame.pack(padx=0, pady=0, fill="both", expand=True)
        
        # Frame dengan warna solid
        frame = tk.Frame(outer_frame, bg=bg_color, bd=0)
        frame.pack(fill="both", expand=True)
        
        # Tambahkan ikon berdasarkan tipe
        icon_map = {
            "info": "ℹ️",
            "success": "✅",
            "warning": "⚠️",
            "error": "❌"
        }
        icon = icon_map.get(type, "ℹ️")
        
        # Header dengan ikon dan judul
        header_frame = tk.Frame(frame, bg=bg_color)
        header_frame.pack(fill="x", padx=10, pady=(10, 5))
        
        icon_label = tk.Label(header_frame, text=icon, font=("Segoe UI Emoji", 20), 
                           bg=bg_color, fg="white")
        icon_label.pack(side="left", padx=(0, 8))
        
        title_label = tk.Label(header_frame, text=title, font=("Montserrat", 14, "bold"), 
                            bg=bg_color, fg="white")
        title_label.pack(side="left")
        
        # Garis pemisah
        separator = tk.Frame(frame, height=1, bg="white")
        separator.pack(fill="x", padx=15, pady=3)
        
        # Pesan
        message_label = tk.Label(frame, text=message, font=("Roboto", 12), 
                              bg=bg_color, fg="white",
                              justify="center", wraplength=350)
        message_label.pack(padx=15, pady=(5, 15))
        
        # Posisikan di tengah atas layar
        toast.update_idletasks()
        width = max(350, toast.winfo_width())
        height = toast.winfo_height()
        x = (toast.winfo_screenwidth() // 2) - (width // 2)
        y = 40  # Jarak dari atas layar
        toast.geometry(f'{width}x{height}+{x}+{y}')
        
        # Animasi fade-in
        toast.attributes("-alpha", 0.0)
        for i in range(1, 11):
            toast.attributes("-alpha", i/10)
            toast.update()
            time.sleep(0.02)
        
        # Jadwalkan penghilangan toast setelah beberapa detik
        def fade_out():
            for i in range(10, -1, -1):
                toast.attributes("-alpha", i/10)
                toast.update()
                time.sleep(0.02)
            toast.destroy()
            
        toast.after(3000, fade_out)
        
    def show_success_popup(self, title, message):
        """
        Menampilkan popup dialog sukses yang lebih jelas
        """
        # Buat window popup
        popup = tk.Toplevel(self.root)
        popup.title(title)
        popup.geometry("500x450")  # Ukuran diperbesar agar konten tidak terpotong
        popup.configure(bg=self.colors["success"])
        popup.resizable(True, True)  # Buat resizable agar bisa disesuaikan jika perlu
        
        # Posisikan di tengah layar
        popup.update_idletasks()
        x = (popup.winfo_screenwidth() // 2) - (popup.winfo_width() // 2)
        y = (popup.winfo_screenheight() // 2) - (popup.winfo_height() // 2)
        popup.geometry(f"+{x}+{y}")
        
        # Selalu di atas
        popup.attributes("-topmost", True)
        popup.grab_set()  # Modal dialog
        
        # Header frame
        header_frame = tk.Frame(popup, bg=self.colors["success"], pady=15)
        header_frame.pack(fill="x")
        
        # Ikon sukses besar
        icon_label = tk.Label(header_frame, text="✅", font=("Segoe UI Emoji", 48), 
                            bg=self.colors["success"], fg="white")
        icon_label.pack()
        
        # Judul
        title_label = tk.Label(header_frame, text=title, font=("Montserrat", 18, "bold"), 
                             bg=self.colors["success"], fg="white")
        title_label.pack(pady=(10, 0))
        
        # Content frame
        content_frame = tk.Frame(popup, bg="white", padx=20, pady=20)
        content_frame.pack(fill="both", expand=True, padx=10, pady=(0, 10))
        
        # Pesan
        message_label = tk.Label(content_frame, text=message, font=("Roboto", 12), 
                               bg="white", fg=self.colors["bg_dark"],
                               justify="center", wraplength=430)  # Tambahkan wraplength untuk menghindari teks terpotong
        message_label.pack(pady=10)
        
        # Tombol OK/TUTUP yang lebih besar dan jelas
        ok_button = tk.Button(content_frame, text="TUTUP", font=("Montserrat", 14, "bold"),
                            bg=self.colors["success"], fg="white", 
                            padx=40, pady=10, cursor="hand2",
                            command=popup.destroy)
        ok_button.pack(pady=20)
        
        # Auto close setelah 8 detik (waktu lebih lama untuk membaca)
        popup.after(8000, popup.destroy)
        
    def send_fcm_notification(self, user_data, transaction_data, saldo_lama, saldo_baru):
        """
        Mengirim notifikasi FCM ke mobile app melalui Laravel API
        """
        try:
            # Login dulu untuk mendapatkan token
            auth_response = requests.post(
                f"{self.api_url}/auth/login",
                json={
                    "email": user_data.get('email', ''),
                    "password": "password123"  # Sesuaikan dengan password yang benar
                },
                timeout=10
            )
            
            if auth_response.status_code == 200:
                auth_data = auth_response.json()
                auth_token = auth_data.get('token')
                
                if auth_token:
                    # Format pesan notifikasi
                    nama = user_data.get('nama_lengkap', 'Pengguna')
                    plat = transaction_data.get('plat_nomor', 'N/A')
                    tarif = transaction_data.get('tarif', 0)
                    
                    title = "🚗 Transaksi Tol Berhasil"
                    body = f"Halo {nama}! Transaksi untuk kendaraan {plat} berhasil. "
                    body += f"Tarif: Rp {tarif:,.0f}. Saldo akhir: Rp {saldo_baru:,.0f}".replace(",", ".")
                    
                    # Kirim notifikasi melalui API
                    notif_response = requests.post(
                        f"{self.api_url}/notify-transaction",
                        json={
                            "title": title,
                            "body": body,
                            "data": {
                                "type": "transaction",
                                "transaction_id": transaction_data.get('id'),
                                "plat_nomor": plat,
                                "tarif": str(tarif),
                                "saldo_baru": str(saldo_baru),
                                "nama": nama
                            }
                        },
                        headers={
                            "Authorization": f"Bearer {auth_token}",
                            "Content-Type": "application/json"
                        },
                        timeout=10
                    )
                    
                    if notif_response.status_code == 200:
                        print(f"📱 FCM notification sent successfully to {nama}")
                    else:
                        print(f"❌ FCM notification failed: {notif_response.status_code}")
                        if notif_response.status_code != 404:  # Avoid spam for missing endpoints
                            print(f"Response: {notif_response.text}")
                else:
                    print("❌ No auth token received")
            else:
                print(f"❌ Authentication failed: {auth_response.status_code}")
                
        except Exception as e:
            print(f"❌ Error sending FCM notification: {str(e)}")

    def play_success_sound(self):
        """
        Memainkan suara beep saat transaksi berhasil
        """
        try:
            # Gunakan file audio MP3 yang disediakan
            if pygame and pygame.mixer.get_init():
                sound_file = "beep-401570.mp3"
                if os.path.exists(sound_file):
                    sound = pygame.mixer.Sound(sound_file)
                    sound.play()
                else:
                    print(f"File audio {sound_file} tidak ditemukan")
                    print('\a')  # Fallback bell character
            else:
                # Fallback jika pygame tidak tersedia
                print('\a')  # Bell character
        except Exception as e:
            print(f"Error playing sound: {e}")
            print('\a')  # Fallback bell character
    
    def flash_auto_indicator(self):
        """
        Flash indikator AUTO untuk menunjukkan auto-select sedang aktif
        """
        if hasattr(self, 'auto_indicator'):
            # Flash dengan warna yang berbeda
            original_color = self.auto_indicator.cget("fg")
            self.auto_indicator.config(fg=self.colors["accent_light"])
            
            # Kembalikan ke warna asli setelah 500ms
            self.root.after(500, lambda: self.auto_indicator.config(fg=original_color))
        
    def show_gate_notification(self, is_open):
        """
        Menampilkan notifikasi popup saat status palang berubah
        """
        if is_open and self.current_user_data:
            try:
                saldo_lama = float(self.current_user_data.get('previous_balance', 0))
                saldo_baru = float(self.current_user_data.get('current_balance', self.current_user_data.get('saldo', 0)))
                nama = self.current_user_data.get('nama_lengkap', 'Pengguna')
                
                # Format saldo dengan pemisah ribuan
                saldo_lama_fmt = f"Rp {saldo_lama:,.0f}".replace(",", ".")
                saldo_baru_fmt = f"Rp {saldo_baru:,.0f}".replace(",", ".")
                
                # Buat pesan transaksi
                message = f"✅ TRANSAKSI BERHASIL!\n\n"
                message += f"Nama: {nama}\n"
                message += f"Saldo Awal: {saldo_lama_fmt}\n"
                message += f"Saldo Akhir: {saldo_baru_fmt}\n\n"
                message += f"🚧 PALANG TERBUKA 🚧\nSilakan melintas!"
                
                # Tampilkan sebagai popup dialog
                self.show_success_popup("TRANSAKSI SUKSES", message)
                
                # Mainkan suara beep
                self.play_success_sound()
                
            except Exception as e:
                # Fallback jika ada error
                self.show_success_popup("TRANSAKSI SUKSES", "🚧 PALANG TERBUKA 🚧\nSilakan melintas!")
                self.play_success_sound()
        elif is_open:
            self.show_success_popup("TRANSAKSI SUKSES", "🚧 PALANG TERBUKA 🚧\nSilakan melintas!")
            self.play_success_sound()
    
    def create_widgets(self):
        # Header with gradient effect
        header_frame = self.create_gradient_frame(self.root, self.colors["bg_dark"], self.colors["bg_light"], height=80)  # Increased header height
        
        # Load school logo
        try:
            logo_path = "/home/archmam/AndroidStudioProjects/lomba/plat_nomor_backend/img/images.png"
            logo_image = Image.open(logo_path)
            # Resize logo to appropriate height while maintaining aspect ratio
            logo_height = 65  # Increased desired height
            aspect_ratio = logo_image.width / logo_image.height
            logo_width = int(logo_height * aspect_ratio)
            logo_image = logo_image.resize((logo_width, logo_height), Image.LANCZOS)
            logo_photo = ImageTk.PhotoImage(logo_image)
            
            # Create label for logo
            self.logo_image_label = tk.Label(header_frame, image=logo_photo, bg=self.colors["bg_medium"])
            self.logo_image_label.image = logo_photo  # Keep a reference to prevent garbage collection
            self.logo_image_label.place(x=20, y=7)  # Adjusted y position for vertical centering
            
            # Adjust position for text after logo
            logo_offset = logo_width + 30
        except Exception as e:
            print(f"Error loading school logo: {e}")
            logo_offset = 20  # Default offset if logo fails to load
        
        # Logo text next to school logo
        logo_text = "EZToll"
        logo_label = tk.Label(header_frame, text=logo_text, font=("Montserrat", 28, "bold"), 
                            fg=self.colors["accent_light"], bg=self.colors["bg_medium"])
        logo_label.place(x=logo_offset, y=17)  # Adjusted y position for vertical centering with larger font
        
        title_text = "AUTOMATIC LICENSE PLATE RECOGNITION"
        title_label = tk.Label(header_frame, text=title_text, font=self.title_font, 
                             fg=self.colors["text_light"], bg=self.colors["bg_medium"])
        title_label.place(x=logo_offset + 150, y=22)  # Adjusted position based on larger logo and text  # Adjusted position based on logo
        
        self.clock_label = tk.Label(header_frame, font=self.label_font, 
                                  fg=self.colors["text_light"], bg=self.colors["bg_medium"])
        self.clock_label.place(relx=1.0, y=25, anchor="e", x=-20)

        # Main content area with gradient background
        main_bg = self.create_gradient_frame(self.root, self.colors["bg_medium"], self.colors["bg_dark"], height=5000)
        main_frame = tk.Frame(main_bg, bg=self.colors["bg_medium"])
        main_frame.place(relx=0.5, rely=0.5, anchor="center", relwidth=0.98, relheight=0.9)
        
        main_frame.grid_columnconfigure(0, weight=3)
        main_frame.grid_columnconfigure(1, weight=2)
        main_frame.grid_rowconfigure(0, weight=1)

        # Left panel - Camera/Image view
        left_frame = tk.Frame(main_frame, bg=self.colors["bg_light"], bd=0)
        left_frame.grid(row=0, column=0, sticky="nsew", padx=(0, 10))
        
        # Camera header with icon
        camera_header = tk.Frame(left_frame, bg=self.colors["accent"], height=40)
        camera_header.pack(fill="x")
        
        camera_icon = "📹"  # Camera emoji as icon
        camera_icon_label = tk.Label(camera_header, text=camera_icon, font=("Segoe UI Emoji", 16), 
                                   fg=self.colors["text_light"], bg=self.colors["accent"])
        camera_icon_label.pack(side="left", padx=10)
        
        camera_label = tk.Label(camera_header, text="KAMERA GERBANG TOL", font=self.subtitle_font, 
                              fg=self.colors["text_light"], bg=self.colors["accent"])
        camera_label.pack(side="left", padx=5)
        
        # Emergency stop button in top-right of camera view
        self.emergency_stop_btn = RoundedButton(left_frame, width=50, height=50, corner_radius=25,
                                              color="#e74c3c", text="⏹", command=self.stop_processing)
        self.emergency_stop_btn.place(relx=0.95, rely=0.05, anchor="ne")
        self.emergency_stop_btn.set_state("disabled")
        
        # Camera view with border effect
        camera_container = tk.Frame(left_frame, bg=self.colors["bg_dark"], bd=2, relief="sunken", padx=2, pady=2)
        camera_container.pack(fill="both", expand=True, padx=15, pady=15)
        
        self.image_frame = tk.Frame(camera_container, bg=self.colors["bg_dark"])
        self.image_frame.pack(fill="both", expand=True)
        
        # Placeholder text with icon
        placeholder_frame = tk.Frame(self.image_frame, bg=self.colors["bg_dark"])
        placeholder_frame.place(relx=0.5, rely=0.5, anchor="center")
        
        camera_big_icon = tk.Label(placeholder_frame, text="🎥", font=("Segoe UI Emoji", 48), 
                                 fg=self.colors["text_light"], bg=self.colors["bg_dark"])
        camera_big_icon.pack()
        
        self.image_label = tk.Label(self.image_frame, bg=self.colors["bg_dark"], 
                                  text="Pilih sumber media untuk memulai", 
                                  fg=self.colors["text_light"], font=self.label_font)
        self.image_label.place(relx=0.5, rely=0.6, anchor="center")

        # Control buttons under camera view
        control_frame = tk.Frame(left_frame, bg=self.colors["bg_light"], pady=15)
        control_frame.pack(fill="x", padx=15, pady=(0, 15))
        
        # Use grid with 3 columns
        control_frame.columnconfigure((0, 1, 2), weight=1)
        
        # Create rounded buttons
        button_height = 40
        
        self.btn_image = RoundedButton(control_frame, width=180, height=button_height, corner_radius=20,
                                     color=self.colors["accent"], text="PILIH GAMBAR", command=self.process_image)
        self.btn_image.grid(row=0, column=0, padx=10, pady=5)
        
        self.btn_video = RoundedButton(control_frame, width=180, height=button_height, corner_radius=20,
                                     color=self.colors["accent"], text="PILIH VIDEO", command=self.start_video_processing)
        self.btn_video.grid(row=0, column=1, padx=10, pady=5)
        
        self.btn_webcam = RoundedButton(control_frame, width=180, height=button_height, corner_radius=20,
                                      color=self.colors["accent"], text="BUKA WEBCAM", command=self.start_webcam_processing)
        self.btn_webcam.grid(row=0, column=2, padx=10, pady=5)
        
        self.btn_stop = RoundedButton(control_frame, width=180, height=button_height, corner_radius=20,
                                    color=self.colors["error"], text="STOP", command=self.stop_processing)
        self.btn_stop.grid(row=1, column=1, padx=10, pady=10)
        self.btn_stop.set_state("disabled")

        # Right panel - Results and controls
        right_frame = tk.Frame(main_frame, bg=self.colors["bg_light"])
        right_frame.grid(row=0, column=1, sticky="nsew", padx=(10, 0))
        
        # Tarif selection with nice header
        tarif_header = tk.Frame(right_frame, bg=self.colors["accent"], height=40)
        tarif_header.pack(fill="x")
        
        tarif_icon = "🚗"  # Car emoji as icon
        tarif_icon_label = tk.Label(tarif_header, text=tarif_icon, font=("Segoe UI Emoji", 16), 
                                  fg=self.colors["text_light"], bg=self.colors["accent"])
        tarif_icon_label.pack(side="left", padx=10)
        
        tarif_title = tk.Label(tarif_header, text="JENIS KENDARAAN", font=self.subtitle_font, 
                             fg=self.colors["text_light"], bg=self.colors["accent"])
        tarif_title.pack(side="left", padx=5)
        
        # Tarif selection in a nice container
        tarif_container = tk.Frame(right_frame, bg=self.colors["bg_medium"], padx=15, pady=15)
        tarif_container.pack(fill="x")
        
        # Dropdown with icon
        tarif_select_frame = tk.Frame(tarif_container, bg=self.colors["bg_medium"])
        tarif_select_frame.pack(fill="x")
        
        self.tarif_var = tk.StringVar()
        self.tarif_combo = ttk.Combobox(tarif_select_frame, textvariable=self.tarif_var, 
                                      state="readonly", font=("Roboto", 12), height=10)
        self.tarif_combo.pack(fill="x", pady=5, padx=5, side="left", expand=True)
        
        # Auto-select indicator
        self.auto_indicator = tk.Label(tarif_select_frame, text="🤖 AUTO", font=("Roboto", 10, "bold"),
                                     fg=self.colors["success"], bg=self.colors["bg_medium"])
        self.auto_indicator.pack(side="right", padx=5)
        
        # Refresh tarif button
        refresh_btn = RoundedButton(tarif_select_frame, width=40, height=40, corner_radius=20,
                                 color=self.colors["accent"], text="🔄", command=self.load_tarifs)
        refresh_btn.pack(side="right", padx=5)
        
        # Results display with card-like design
        result_container = tk.Frame(right_frame, bg=self.colors["bg_medium"], padx=15, pady=15)
        result_container.pack(fill="x", pady=15)
        
        result_frame = tk.Frame(result_container, bg=self.colors["bg_dark"], 
                              bd=2, relief="raised", padx=20, pady=20)
        result_frame.pack(fill="x")
        
        # Header with icon
        result_header = tk.Frame(result_frame, bg=self.colors["bg_dark"])
        result_header.pack(fill="x")
        
        plate_icon = "🔍"  # Magnifying glass emoji
        plate_icon_label = tk.Label(result_header, text=plate_icon, font=("Segoe UI Emoji", 24), 
                                  fg=self.colors["accent_light"], bg=self.colors["bg_dark"])
        plate_icon_label.pack(side="left")
        
        tk.Label(result_header, text="PLAT NOMOR TERDETEKSI", font=self.subtitle_font, 
               fg=self.colors["text_light"], bg=self.colors["bg_dark"]).pack(side="left", padx=10)
        
        # Plate result with glow effect
        plate_frame = tk.Frame(result_frame, bg=self.colors["bg_dark"], pady=15)
        plate_frame.pack(fill="x")
        
        # Create plate display with car license plate style
        plate_display = tk.Frame(plate_frame, bg=self.colors["bg_dark"], padx=10, pady=10)
        plate_display.pack()
        
        # License plate with border
        license_frame = tk.Frame(plate_display, bd=2, relief="raised", 
                               bg=self.colors["text_light"], padx=15, pady=10)
        license_frame.pack()
        
        self.result_plate_label = tk.Label(license_frame, text="-", 
                                         font=self.result_font, 
                                         fg=self.colors["bg_dark"], 
                                         bg=self.colors["text_light"])
        self.result_plate_label.pack()
        
        # Cropped plate image with border
        cropped_frame = tk.Frame(result_frame, bg=self.colors["bg_dark"], pady=10)
        cropped_frame.pack()
        
        self.cropped_plate_label = tk.Label(cropped_frame, bg=self.colors["bg_dark"])
        self.cropped_plate_label.pack()
        
        # Confidence display with progress bar
        confidence_frame = tk.Frame(result_frame, bg=self.colors["bg_dark"], pady=10)
        confidence_frame.pack(fill="x")
        
        self.confidence_label = tk.Label(confidence_frame, text="Keyakinan: - %", 
                                       font=self.label_font, fg=self.colors["text_light"], 
                                       bg=self.colors["bg_dark"])
        self.confidence_label.pack(side="left")
        
        self.confidence_bar = ttk.Progressbar(confidence_frame, length=200, mode="determinate")
        self.confidence_bar.pack(side="right", padx=10)
        
        # Process button - big and attractive
        self.btn_process = RoundedButton(result_frame, width=280, height=50, corner_radius=25,
                                       color=self.colors["success"], text="PROSES TRANSAKSI", 
                                       command=self.process_transaction)
        self.btn_process.pack(pady=20)
        self.btn_process.set_state("disabled")
        
        # Transaction history with nice header
        history_header = tk.Frame(right_frame, bg=self.colors["accent"], height=40)
        history_header.pack(fill="x", pady=(15, 0))
        
        history_icon = "📊"  # Chart emoji
        history_icon_label = tk.Label(history_header, text=history_icon, font=("Segoe UI Emoji", 16), 
                                    fg=self.colors["text_light"], bg=self.colors["accent"])
        history_icon_label.pack(side="left", padx=10)
        
        history_title = tk.Label(history_header, text="HISTORI TRANSAKSI", font=self.subtitle_font, 
                               fg=self.colors["text_light"], bg=self.colors["accent"])
        history_title.pack(side="left", padx=5)
        
        # Transaction history in a nice container
        history_container = tk.Frame(right_frame, bg=self.colors["bg_medium"], padx=15, pady=15)
        history_container.pack(fill="both", expand=True)
        
        # Treeview for history with improved styling
        cols = ("Waktu", "Plat Nomor", "Golongan", "Tarif", "Status")
        self.history_tree = ttk.Treeview(history_container, columns=cols, show="headings", height=10)
        
        # Configure column headings with icons
        icons = {"Waktu": "🕒", "Plat Nomor": "🚗", "Golongan": "📋", "Tarif": "💰", "Status": "✅"}
        icons["Golongan"] = "🚘"  # Ubah ikon untuk jenis kendaraan
        for col in cols:
            text = "Jenis" if col == "Golongan" else col  # Ubah label Golongan menjadi Jenis
            self.history_tree.heading(col, text=f"{icons[col]} {text}")
        
        # Configure column widths
        self.history_tree.column("Waktu", width=100)
        self.history_tree.column("Plat Nomor", width=120)
        self.history_tree.column("Golongan", width=100)
        self.history_tree.column("Tarif", width=100, anchor="e")
        self.history_tree.column("Status", width=100, anchor="center")
        
        # Custom scrollbar
        scrollbar = ttk.Scrollbar(history_container, orient="vertical", command=self.history_tree.yview)
        self.history_tree.configure(yscrollcommand=scrollbar.set)
        
        # Pack treeview and scrollbar
        self.history_tree.pack(side="left", fill="both", expand=True)
        scrollbar.pack(side="right", fill="y")
        
        # Toolbar with additional functions
        toolbar_frame = tk.Frame(self.root, bg=self.colors["bg_medium"], height=40)
        toolbar_frame.pack(side="bottom", fill="x")
        
        # Create toolbar buttons
        toolbar_buttons = [
            {"icon": "📊", "text": "Statistik", "command": self.show_statistics},
            {"icon": "🎨", "text": "Tema", "command": self.toggle_theme},
            {"icon": "✨", "text": "Animasi", "command": self.toggle_animation},
            {"icon": "⚙️", "text": "Pengaturan Frame", "command": self.show_frame_settings},
            {"icon": "🗑️", "text": "Reset Histori", "command": self.reset_history},
            {"icon": "❓", "text": "Bantuan", "command": lambda: messagebox.showinfo("Bantuan", 
                                                                                 "Sistem Gerbang Tol Otomatis\n\n"
                                                                                 "1. Pilih sumber input (gambar, video, webcam)\n"
                                                                                 "2. Sistem akan mendeteksi plat nomor\n"
                                                                                 "3. Pilih golongan kendaraan\n"
                                                                                 "4. Klik 'Proses Transaksi' untuk melakukan pembayaran\n\n"
                                                                                 "FITUR FRAME SKIPPING:\n"
                                                                                 "• Meningkatkan performa dengan melewati beberapa frame\n"
                                                                                 "• Mode adaptif: Menyesuaikan otomatis berdasarkan FPS\n"
                                                                                 "• Klik 'Pengaturan Frame' di toolbar untuk mengatur\n"
                                                                                 "• Nilai skip lebih tinggi = performa lebih baik, deteksi lebih jarang")}
        ]
        
        # Add buttons to toolbar
        for i, btn in enumerate(toolbar_buttons):
            button = tk.Frame(toolbar_frame, bg=self.colors["bg_medium"], padx=10, pady=5, cursor="hand2")
            button.pack(side="left")
            
            icon = tk.Label(button, text=btn["icon"], font=("Segoe UI Emoji", 16), 
                          bg=self.colors["bg_medium"], fg=self.colors["accent_light"])
            icon.pack()
            
            text = tk.Label(button, text=btn["text"], font=self.status_font, 
                          bg=self.colors["bg_medium"], fg=self.colors["text_light"])
            text.pack()
            
            # Bind click event
            button.bind("<Button-1>", lambda e, cmd=btn["command"]: cmd())
            icon.bind("<Button-1>", lambda e, cmd=btn["command"]: cmd())
            text.bind("<Button-1>", lambda e, cmd=btn["command"]: cmd())
        
        # Status bar with icon and better styling
        status_frame = tk.Frame(self.root, bg=self.colors["bg_medium"], height=30)
        status_frame.pack(side="bottom", fill="x")
        
        status_icon = tk.Label(status_frame, text="ℹ️", font=("Segoe UI Emoji", 12), 
                             bg=self.colors["bg_medium"])
        status_icon.pack(side="left", padx=5)
        
        self.status_bar = tk.Label(status_frame, text="Sistem siap", font=self.status_font,
                                 anchor="w", fg=self.colors["text_light"], bg=self.colors["bg_medium"])
        self.status_bar.pack(side="left", fill="x", expand=True)
        
        # Tambahkan indikator status palang dengan tampilan yang lebih mencolok
        gate_indicator_frame = tk.Frame(status_frame, bg=self.colors["bg_dark"], bd=2, relief="raised", padx=5, pady=3)
        gate_indicator_frame.pack(side="right", padx=15)
        
        self.gate_indicator_label = tk.Label(gate_indicator_frame, text="🚧 PALANG TERTUTUP 🚧", 
                                          font=("Segoe UI Emoji", 12, "bold"),
                                          fg=self.colors["error"], bg=self.colors["bg_dark"])
        self.gate_indicator_label.pack(padx=5, pady=2)
        
        
        # FPS display
        self.fps_label = tk.Label(status_frame, text="FPS: 0", font=self.status_font,
                                fg=self.colors["accent_light"], bg=self.colors["bg_medium"])
        self.fps_label.pack(side="right", padx=5)
        
        # Frame skip info
        self.skip_label = tk.Label(status_frame, text="Skip: 0", font=self.status_font,
                                 fg=self.colors["accent_light"], bg=self.colors["bg_medium"])
        self.skip_label.pack(side="right", padx=5)
        
        # Add version info
        version_label = tk.Label(status_frame, text="v1.1.0", font=self.status_font,
                               fg=self.colors["text_light"], bg=self.colors["bg_medium"])
        version_label.pack(side="right", padx=10)

    def update_clock(self):
        now = datetime.now().strftime("%A, %d %B %Y | %H:%M:%S")
        self.clock_label.config(text=now)
        
        # Cek status palang jika terbuka
        if self.gate_open:
            elapsed = time.time() - self.gate_open_time
            remaining = max(0, self.gate_open_duration - elapsed)
            
            if remaining > 0:
                # Update indikator dengan sisa waktu
                self.gate_indicator_label.config(text=f"🚧 PALANG TERBUKA 🚧 ({int(remaining)}s)")
        
        self.root.after(1000, self.update_clock)
    
    def load_tarifs(self):
        try:
            # Coba ambil dari API
            print(f"Mencoba mengakses API: {self.api_url}/tarifs")
            response = requests.get(f"{self.api_url}/tarifs", timeout=5)
            print(f"Response status: {response.status_code}")
            
            if response.status_code == 200:
                response_data = response.json()
                print(f"Response data: {response_data}")
                
                # Cek apakah response memiliki format baru dengan 'data' field
                if 'data' in response_data:
                    self.tarifs = response_data['data']
                else:
                    self.tarifs = response_data
                    
                if self.tarifs:
                    tarif_options = [f"{t['kelompok_kendaraan']} - Rp {float(t['harga']):,.0f}".replace(",", ".") for t in self.tarifs]
                    self.tarif_combo['values'] = tarif_options
                    if tarif_options:
                        self.tarif_combo.current(0)
                    self.status_bar.config(text="Tarif berhasil dimuat dari API")
                    print(f"Tarif dimuat: {len(self.tarifs)} items")
                else:
                    self.status_bar.config(text="Data tarif kosong dari API")
                    print("Data tarif kosong")
            else:
                # Jika gagal, tampilkan error
                self.status_bar.config(text=f"API error: status {response.status_code}")
                print(f"API error: {response.status_code} - {response.text}")
                messagebox.showerror("Error", f"API error: status {response.status_code}. Pastikan server Laravel berjalan di http://127.0.0.1:8080")
        except requests.exceptions.ConnectionError as e:
            # Jika koneksi gagal
            error_msg = "Tidak dapat terhubung ke server API"
            self.status_bar.config(text=error_msg)
            print(f"Connection error: {str(e)}")
            messagebox.showerror("Error Koneksi", f"{error_msg}. Pastikan server Laravel berjalan di http://127.0.0.1:8080")
        except requests.exceptions.Timeout as e:
            # Jika timeout
            error_msg = "Timeout saat mengakses API"
            self.status_bar.config(text=error_msg)
            print(f"Timeout error: {str(e)}")
            messagebox.showerror("Error Timeout", f"{error_msg}. Server mungkin sedang sibuk.")
        except Exception as e:
            # Jika error lain
            error_msg = f"Error memuat tarif: {str(e)}"
            self.status_bar.config(text=error_msg)
            print(f"General error: {str(e)}")
            messagebox.showerror("Error", error_msg)

    def process_image(self):
        if self.processing_active:
            return
            
        file_path = filedialog.askopenfilename(filetypes=[("Image Files", "*.jpg *.jpeg *.png")])
        if not file_path:
            return

        self.status_bar.config(text=f"Memproses gambar: {os.path.basename(file_path)}...")
        self.root.update_idletasks()
        
        # Baca gambar untuk klasifikasi dan deteksi
        img = cv2.imread(file_path)
        if img is None:
            self.status_bar.config(text="Gagal memuat gambar!")
            return
        
        # Klasifikasi jenis kendaraan
        self.detected_vehicle, all_vehicles = classify_vehicle(img, self.vehicle_model)
        
        # Reset kendaraan yang terdeteksi
        self.detected_vehicles = {}
        
        # Deteksi plat nomor
        plate_number, img_hasil, img_potongan, confidence = detect_plate_yolo_and_ocr(
            img, self.yolo_model, self.ocr_reader
        )
        
        # Tambahkan kendaraan yang terdeteksi ke tracking
        vehicle_id = self.next_vehicle_id
        self.next_vehicle_id += 1
        
        self.detected_vehicles[vehicle_id] = {
            "vehicle": self.detected_vehicle,
            "last_seen": time.time(),
            "plate": None
        }
        
        # Visualisasi hasil klasifikasi kendaraan pada gambar
        if self.detected_vehicle["box"] is not None:
            x1, y1, x2, y2 = self.detected_vehicle["box"]
            vehicle_name = self.detected_vehicle["name"]
            vehicle_conf = self.detected_vehicle["conf"]
            
            # Gambar bounding box untuk kendaraan dengan warna berbeda berdasarkan jenis
            color_map = {
                "Mobil": (0, 255, 0),     # Hijau
                "Bus": (0, 0, 255),       # Biru
                "Truk": (255, 0, 0)       # Merah
            }
            
            color = color_map.get(vehicle_name, (0, 255, 255))
            
            # Cek apakah plat nomor terdeteksi
            plate_detected = plate_number not in ["Tidak terbaca", "Plat tidak terdeteksi"]
            
            # Hanya gambar kotak jika plat nomor terdeteksi
            if plate_detected:
                cv2.rectangle(img_hasil, (x1, y1), (x2, y2), color, 3)
                
                # Tambahkan label dengan informasi kendaraan dan ID
                label = f"ID:{vehicle_id} {vehicle_name} ({vehicle_conf*100:.1f}%)"
                cv2.rectangle(img_hasil, (x1, y1-30), (x1+len(label)*11, y1), color, -1)
                cv2.putText(img_hasil, label, (x1, y1-10), 
                           cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255, 255, 255), 2)
            
            # Auto-select tarif berdasarkan jenis kendaraan
            if self.auto_select_tarif and hasattr(self, 'tarifs') and self.tarifs:
                tarif_name = self.vehicle_to_tarif.get(vehicle_name, "Mobil")
                # Set nilai combobox
                for idx, tarif in enumerate(self.tarifs):
                    if tarif["kelompok_kendaraan"] == tarif_name:
                        self.tarif_combo.current(idx)
                        print(f"🖼️ AUTO-SELECT IMAGE: {vehicle_name} -> {tarif_name}")
                        break
            
            # Update statistik
            if vehicle_name in self.stats["vehicle_types"]:
                self.stats["vehicle_types"][vehicle_name] += 1
                
        # Simpan plat nomor yang terdeteksi untuk kendaraan ini
        if plate_number not in ["Tidak terbaca", "Plat tidak terdeteksi"]:
            self.detected_vehicles[vehicle_id]["plate"] = plate_number
        
        self.update_ui_results(plate_number, img_hasil, img_potongan, confidence)
        
        if plate_number != "Tidak terbaca" and plate_number != "Plat tidak terdeteksi":
            self.btn_process.set_state("normal")
        else:
            self.btn_process.set_state("disabled")
            
        self.status_bar.config(text=f"Selesai memproses gambar. Kendaraan terdeteksi: {self.detected_vehicle['name']}")

    def start_video_processing(self):
        if self.processing_active:
            return
            
        file_path = filedialog.askopenfilename(filetypes=[("Video Files", "*.mp4 *.avi *.mov")])
        if not file_path:
            return
            
        self.start_processing_thread(file_path)

    def start_webcam_processing(self):
        if self.processing_active:
            return
        
        # Cek webcam yang tersedia
        available_cameras = self.get_available_cameras()
        
        if not available_cameras:
            messagebox.showerror("Error", "Tidak ada webcam yang terdeteksi")
            return
        
        if len(available_cameras) == 1:
            # Jika hanya ada satu webcam, gunakan langsung
            self.start_processing_thread(0)
        else:
            # Tampilkan dialog untuk memilih webcam
            self.show_webcam_selection_dialog(available_cameras)
            
    def get_available_cameras(self):
        """
        Mendeteksi semua webcam yang tersedia
        """
        available_cameras = []
        # Cek kamera dari index 0-5 (umumnya tidak lebih dari ini)
        for i in range(6):
            cap = cv2.VideoCapture(i)
            if cap.isOpened():
                # Baca info dari kamera
                ret, frame = cap.read()
                if ret:
                    camera_name = f"Kamera {i+1}"
                    if i == 0:
                        camera_name += " (Laptop Webcam)"
                    available_cameras.append((i, camera_name))
                cap.release()
        return available_cameras
    
    def show_webcam_selection_dialog(self, available_cameras):
        """
        Menampilkan dialog untuk memilih webcam
        """
        dialog = tk.Toplevel(self.root)
        dialog.title("Pilih Webcam")
        dialog.geometry("400x300")
        dialog.configure(bg=self.colors["bg_medium"])
        dialog.transient(self.root)
        dialog.grab_set()
        
        # Center the dialog
        dialog.update_idletasks()
        width = dialog.winfo_width()
        height = dialog.winfo_height()
        x = (dialog.winfo_screenwidth() // 2) - (width // 2)
        y = (dialog.winfo_screenheight() // 2) - (height // 2)
        dialog.geometry(f'+{x}+{y}')
        
        # Header
        header = tk.Frame(dialog, bg=self.colors["accent"], height=40)
        header.pack(fill="x")
        
        webcam_icon = "📹"
        webcam_icon_label = tk.Label(header, text=webcam_icon, font=("Segoe UI Emoji", 16), 
                                   fg=self.colors["text_light"], bg=self.colors["accent"])
        webcam_icon_label.pack(side="left", padx=10)
        
        webcam_title = tk.Label(header, text="PILIH WEBCAM", font=self.subtitle_font, 
                             fg=self.colors["text_light"], bg=self.colors["accent"])
        webcam_title.pack(side="left", padx=5)
        
        # Instructions
        instruction = tk.Label(dialog, text="Pilih webcam yang ingin digunakan:",
                            font=self.label_font, fg=self.colors["text_light"], 
                            bg=self.colors["bg_medium"])
        instruction.pack(pady=(20, 10))
        
        # Camera list frame
        list_frame = tk.Frame(dialog, bg=self.colors["bg_medium"], padx=20, pady=10)
        list_frame.pack(fill="both", expand=True)
        
        # Create buttons for each camera
        for idx, (cam_id, cam_name) in enumerate(available_cameras):
            cam_button = RoundedButton(list_frame, width=300, height=40, corner_radius=10,
                                     color=self.colors["accent"], text=cam_name, 
                                     command=lambda id=cam_id: self.select_webcam(id, dialog))
            cam_button.pack(pady=5)
        
        # Cancel button
        cancel_btn = RoundedButton(dialog, width=150, height=40, corner_radius=20,
                                 color=self.colors["error"], text="BATAL", 
                                 command=dialog.destroy)
        cancel_btn.pack(pady=20)
    
    def select_webcam(self, webcam_id, dialog):
        """
        Memilih webcam dan memulai pemrosesan
        """
        dialog.destroy()
        self.start_processing_thread(webcam_id)

    def start_processing_thread(self, source):
        self.processing_active = True
        self.toggle_buttons(False)
        self.status_bar.config(text=f"Memulai pemrosesan dari: {source}")
        
        # Run the processing in a separate thread to keep the GUI responsive
        thread = threading.Thread(target=self.main_processing_loop, args=(source,), daemon=True)
        thread.start()

    def main_processing_loop(self, source):
        self.video_capture = cv2.VideoCapture(source)
        self.last_detected_plate = ""
        self.frame_count = 0
        self.processing_times = []
        self.last_frame_time = time.time()
        self.last_process_time = time.time()

        while self.processing_active:
            # Baca frame dari video/webcam
            ret, frame = self.video_capture.read()
            if not ret:
                self.status_bar.config(text="Video selesai atau sumber tidak tersedia.")
                break
                
            # Hitung FPS
            current_time = time.time()
            time_diff = current_time - self.last_frame_time
            self.current_fps = 1.0 / time_diff if time_diff > 0 else 0
            self.last_frame_time = current_time
            
            # Logic frame skipping - optimasi kecepatan pemrosesan video
            process_this_frame = False
            
            if self.adaptive_skipping:
                # Adaptive skipping berdasarkan performa
                if len(self.processing_times) > 0:
                    avg_proc_time = sum(self.processing_times) / len(self.processing_times)
                    # Jika pemrosesan lambat, tingkatkan frame skipping secara agresif
                    if self.current_fps < self.target_fps and self.frame_skip < 15:
                        self.frame_skip = min(15, self.frame_skip + 2)
                    # Jika pemrosesan cepat, kurangi frame skipping perlahan
                    elif self.current_fps > self.target_fps * 1.5 and self.frame_skip > 1:
                        self.frame_skip = max(1, self.frame_skip - 1)
            
            # Fokus pada deteksi kendaraan dulu - lebih cepat dari OCR
            vehicle_detected = False
            if self.frame_count % (self.frame_skip // 2 + 1) == 0:  # Deteksi kendaraan lebih sering
                # Klasifikasi jenis kendaraan dengan confidence threshold tinggi untuk mempercepat
                self.detected_vehicle, all_vehicles = classify_vehicle(frame, self.vehicle_model)
                
                # Update tracking kendaraan yang terdeteksi
                current_time = time.time()
                self.update_vehicle_tracking(all_vehicles, frame, current_time)
                
                vehicle_detected = (self.detected_vehicle["conf"] > 0.4)  # Hanya proses jika confident
            
            # Hanya proses OCR jika ada kendaraan terdeteksi atau dalam interval frame tertentu
            if (vehicle_detected or self.frame_count % (self.frame_skip + 1) == 0):
                process_this_frame = True
                proc_start_time = time.time()
            
            self.frame_count += 1
            
            # Lewati frame jika tidak diproses
            if not process_this_frame:
                continue
                
            # Update status FPS dan frame skip di UI
            self.fps_label.config(text=f"FPS: {self.current_fps:.1f}")
            self.skip_label.config(text=f"Skip: {self.frame_skip}")
            self.status_bar.config(text=f"Pemrosesan frame {self.frame_count}")
            
            # Deteksi plat nomor hanya jika kendaraan terdeteksi atau pada interval yang ditentukan
            plate_number, img_hasil, img_potongan, confidence = detect_plate_yolo_and_ocr(
                frame, self.yolo_model, self.ocr_reader
            )
            
            # Visualisasi hasil klasifikasi kendaraan pada gambar
            if self.detected_vehicles:
                # Gambar semua kendaraan yang terdeteksi
                for vehicle_id, vehicle_data in self.detected_vehicles.items():
                    vehicle = vehicle_data["vehicle"]
                    if vehicle["box"] is not None:
                        x1, y1, x2, y2 = vehicle["box"]
                        vehicle_name = vehicle["name"]
                        vehicle_conf = vehicle["conf"]
                        
                        # Gambar bounding box untuk kendaraan dengan warna berbeda berdasarkan jenis
                        color_map = {
                            "Mobil": (0, 255, 0),     # Hijau
                            "Bus": (0, 0, 255),       # Biru
                            "Truk": (255, 0, 0)       # Merah
                        }
                        
                        color = color_map.get(vehicle_name, (0, 255, 255))
                        
                        # Cek apakah kendaraan ini memiliki plat nomor terdeteksi
                        has_plate = vehicle_data.get("plate") not in [None, "Tidak terbaca", "Plat tidak terdeteksi"]
                        
                        # Hanya gambar kotak jika kendaraan memiliki plat nomor terdeteksi
                        if has_plate:
                            cv2.rectangle(img_hasil, (x1, y1), (x2, y2), color, 3)
                            
                            # Tambahkan label dengan informasi kendaraan dan ID
                            label = f"ID:{vehicle_id} {vehicle_name} ({vehicle_conf*100:.1f}%)"
                            cv2.rectangle(img_hasil, (x1, y1-30), (x1+len(label)*11, y1), color, -1)
                            cv2.putText(img_hasil, label, (x1, y1-10), 
                                      cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255, 255, 255), 2)
                        
                        # Jika kendaraan ini adalah yang memiliki confidence tertinggi
                        if vehicle == self.detected_vehicle:
                            # Auto-select tarif berdasarkan jenis kendaraan
                            if self.auto_select_tarif and hasattr(self, 'tarifs') and self.tarifs:
                                tarif_name = self.vehicle_to_tarif.get(vehicle_name, "Mobil")  # Default ke Mobil
                                # Set nilai combobox
                                for idx, tarif in enumerate(self.tarifs):
                                    if tarif["kelompok_kendaraan"] == tarif_name:
                                        self.tarif_combo.current(idx)
                                        print(f"🚗 AUTO-SELECT REALTIME: {vehicle_name} -> {tarif_name}")
                                        # Update status untuk menunjukkan auto-select
                                        self.status_bar.config(text=f"🤖 AUTO: {vehicle_name} → {tarif_name}")
                                        # Flash auto indicator
                                        self.flash_auto_indicator()
                                        break
                
                # Update statistik untuk kendaraan utama
                vehicle_name = self.detected_vehicle["name"]
                if vehicle_name in self.stats["vehicle_types"]:
                    self.stats["vehicle_types"][vehicle_name] += 1
            
            self.update_ui_results(plate_number, img_hasil, img_potongan, confidence)

            # Verifikasi plat nomor valid
            if (plate_number != "Tidak terbaca" and plate_number != "Plat tidak terdeteksi"):
                current_time = time.time()
                
                # Format plat nomor untuk konsistensi
                clean_plate = plate_number.replace(" ", "")
                
                # Cek apakah karakter pertama adalah huruf (kode wilayah)
                if len(clean_plate) > 0 and clean_plate[0].isalpha():
                    # Jika karakter pertama adalah huruf, format dengan pola "S 9106 HL"
                    prefix = clean_plate[0]
                    remaining = clean_plate[1:]
                    
                    # Cari batas antara angka dan huruf
                    digit_part = ""
                    letter_part = ""
                    for i, char in enumerate(remaining):
                        if char.isdigit():
                            digit_part += char
                        else:
                            letter_part = remaining[i:]
                            break
                    
                    if digit_part and letter_part:
                        formatted_plate = f"{prefix} {digit_part} {letter_part}"
                    else:
                        formatted_plate = clean_plate
                else:
                    # Jika tidak ada kode wilayah, gunakan format standar
                    plate_parts = plate_number.split()
                    if len(plate_parts) >= 3:
                        formatted_plate = f"{plate_parts[0]} {plate_parts[1]} {plate_parts[2]}"
                    else:
                        formatted_plate = plate_number.strip()
                
                # Buat kunci transaksi unik berdasarkan plat dan jenis kendaraan
                vehicle_name = self.detected_vehicle['name']
                transaction_key = f"{formatted_plate}_{vehicle_name}"
                
                # Cek apakah nomor plat yang sama sudah diproses dalam detik yang sama
                current_second = int(current_time)
                found_duplicate = False
                
                # Periksa semua transaksi yang ada
                for existing_key, transaction_data in self.processed_transactions.items():
                    existing_plate = transaction_data["plate"]
                    existing_time = transaction_data["time"]
                    time_diff = current_time - existing_time
                    
                    # Jika plat nomor sama dan dalam 10 detik terakhir, abaikan
                    if existing_plate == formatted_plate and time_diff < 10:
                        remaining_time = 10 - time_diff
                        self.status_bar.config(text=f"Plat {formatted_plate} sudah diproses, tunggu {int(remaining_time)} detik lagi")
                        found_duplicate = True
                        break
                    # Jika plat nomor sama tapi sudah lebih dari 10 detik, bisa diproses lagi
                    elif existing_plate == formatted_plate:
                        # Tidak perlu diblokir, biarkan diproses lagi
                        pass
                
                if found_duplicate:
                    continue
                
                # Cek plat yang sangat mirip (menggunakan difflib untuk kemiripan string)
                similar_transaction = False
                for existing_key in self.processed_transactions:
                    existing_plate = existing_key.split('_')[0]
                    similarity = difflib.SequenceMatcher(None, formatted_plate, existing_plate).ratio()
                    if similarity > self.plate_similarity_threshold:
                        self.status_bar.config(text=f"Plat {formatted_plate} mirip dengan {existing_plate} yang sudah diproses")
                        similar_transaction = True
                        break
                
                if similar_transaction:
                    continue
                
                # Tampilkan pesan plat terdeteksi
                self.status_bar.config(text=f"Plat nomor {formatted_plate} terdeteksi - {vehicle_name}")
                
                # Proses transaksi otomatis tanpa delay
                self.last_detected_plate = formatted_plate
                self.last_process_time = current_time
                self.process_transaction()
                continue
            
            # Catat waktu pemrosesan untuk adaptive skipping
            if process_this_frame:
                proc_end_time = time.time()
                proc_time = proc_end_time - proc_start_time
                self.processing_times.append(proc_time)
                # Simpan hanya 30 sampel terakhir
                if len(self.processing_times) > 30:
                    self.processing_times.pop(0)
        
        if not self.processing_active:  # If stopped by user
            self.status_bar.config(text="Pemrosesan dihentikan.")
        
        self.stop_processing()

    def stop_processing(self):
        self.processing_active = False
        if self.video_capture:
            self.video_capture.release()
            self.video_capture = None
        self.toggle_buttons(True)
        self.btn_process.set_state("disabled")

    def update_vehicle_tracking(self, new_vehicles, frame, current_time):
        """
        Update tracking kendaraan berdasarkan deteksi baru
        """
        # Reset kendaraan yang terdeteksi - hanya tampilkan kendaraan yang terdeteksi di frame saat ini
        # Ini akan menghapus kendaraan yang tidak terlihat secara langsung tanpa jeda
        self.detected_vehicles = {}
            
        # Tambahkan kendaraan baru yang terdeteksi
        for vehicle in new_vehicles:
            # Cek apakah kendaraan ini sudah ada dalam tracking (berdasarkan posisi)
            matched = False
            for vehicle_id, vehicle_data in self.detected_vehicles.items():
                tracked_vehicle = vehicle_data["vehicle"]
                if tracked_vehicle["box"] is not None and vehicle["box"] is not None:
                    # Hitung IoU (Intersection over Union) untuk menentukan apakah ini kendaraan yang sama
                    box1 = tracked_vehicle["box"]
                    box2 = vehicle["box"]
                    
                    # Hitung area overlap
                    x_left = max(box1[0], box2[0])
                    y_top = max(box1[1], box2[1])
                    x_right = min(box1[2], box2[2])
                    y_bottom = min(box1[3], box2[3])
                    
                    if x_right < x_left or y_bottom < y_top:
                        continue  # Tidak ada overlap
                        
                    intersection_area = (x_right - x_left) * (y_bottom - y_top)
                    box1_area = (box1[2] - box1[0]) * (box1[3] - box1[1])
                    box2_area = (box2[2] - box2[0]) * (box2[3] - box2[1])
                    iou = intersection_area / float(box1_area + box2_area - intersection_area)
                    
                    if iou > 0.3:  # Threshold untuk menentukan kendaraan yang sama
                        # Update kendaraan yang sudah ada
                        if vehicle["conf"] > tracked_vehicle["conf"]:
                            self.detected_vehicles[vehicle_id]["vehicle"] = vehicle
                        self.detected_vehicles[vehicle_id]["last_seen"] = current_time
                        matched = True
                        break
                        
            # Jika tidak ada yang cocok, tambahkan sebagai kendaraan baru
            if not matched:
                new_id = self.next_vehicle_id
                self.next_vehicle_id += 1
                self.detected_vehicles[new_id] = {
                    "vehicle": vehicle,
                    "last_seen": current_time,
                    "plate": None
                }
    
    def update_ui_results(self, plate, processed_img, cropped_img, conf):
        # Update plate text
        self.result_plate_label.config(text=plate if plate and plate not in ["Tidak terbaca", "Plat tidak terdeteksi"] else "-")
        
        # Update confidence text and progress bar
        confidence_pct = conf * 100
        self.confidence_label.config(text=f"Keyakinan: {confidence_pct:.2f}%")
        self.confidence_bar["value"] = confidence_pct
        
        # Set color of progress bar based on confidence
        if confidence_pct > 80:
            self.confidence_bar.configure(style="green.Horizontal.TProgressbar")
        elif confidence_pct > 50:
            self.confidence_bar.configure(style="yellow.Horizontal.TProgressbar")
        else:
            self.confidence_bar.configure(style="red.Horizontal.TProgressbar")

        # Update images
        if processed_img is not None:
            self.display_image(processed_img, self.image_label, max_width=800)
        if cropped_img is not None:
            self.display_image(cropped_img, self.cropped_plate_label, max_width=250)
        else:
            self.cropped_plate_label.config(image='')
        
        # Enable/disable buttons based on detection
        plate_detected = plate not in ["Tidak terbaca", "Plat tidak terdeteksi", "-", None, ""]
        
        # Enable process button if plate detected and not in processing mode
        if plate_detected and not self.processing_active:
            self.btn_process.set_state("normal")
        elif not self.processing_active:
            self.btn_process.set_state("disabled")
            
        # Enable/disable emergency stop button based on processing state
        if self.processing_active:
            self.emergency_stop_btn.set_state("normal")
        else:
            self.emergency_stop_btn.set_state("disabled")
            
        # Jika plat terdeteksi, coba kaitkan dengan kendaraan yang terdeteksi
        if plate_detected:
            # Cari kendaraan yang belum memiliki plat
            for vehicle_id, vehicle_data in self.detected_vehicles.items():
                if vehicle_data["plate"] is None:
                    vehicle_data["plate"] = plate
                    break

    def process_transaction(self):
        plate_number = self.result_plate_label.cget("text")
        
        if plate_number == "-" or plate_number == "Tidak terbaca" or plate_number == "Plat tidak terdeteksi":
            messagebox.showerror("Error", "Tidak ada plat nomor terdeteksi!")
            return
        
        # Format plat nomor untuk konsistensi
        clean_plate = plate_number.replace(" ", "")
        
        # Cek apakah karakter pertama adalah huruf (kode wilayah)
        if len(clean_plate) > 0 and clean_plate[0].isalpha():
            # Jika karakter pertama adalah huruf, format dengan pola "S 9106 HL"
            prefix = clean_plate[0]
            remaining = clean_plate[1:]
            
            # Cari batas antara angka dan huruf
            digit_part = ""
            letter_part = ""
            for i, char in enumerate(remaining):
                if char.isdigit():
                    digit_part += char
                else:
                    letter_part = remaining[i:]
                    break
            
            if digit_part and letter_part:
                formatted_plate = f"{prefix} {digit_part} {letter_part}"
            else:
                formatted_plate = clean_plate
        else:
            # Jika tidak ada kode wilayah, gunakan format standar
            plate_parts = plate_number.split()
            if len(plate_parts) >= 3:
                formatted_plate = f"{plate_parts[0]} {plate_parts[1]} {plate_parts[2]}"
            else:
                formatted_plate = plate_number.strip()
                
        # Update plate_number dengan format yang benar
        plate_number = formatted_plate
            
        # Buat kunci transaksi unik berdasarkan plat dan jenis kendaraan
        vehicle_name = self.detected_vehicle['name']
        transaction_key = f"{plate_number}_{vehicle_name}"
        
        # Cek apakah nomor plat yang sama sudah diproses dalam detik yang sama
        current_time = time.time()
        current_second = int(current_time)
        
        # Periksa semua transaksi yang ada - cek berdasarkan detik yang sama
        for existing_key, transaction_data in self.processed_transactions.items():
            existing_plate = transaction_data["plate"]
            existing_second = transaction_data["second"]
            
            # Jika plat nomor yang sama sudah diproses dalam detik yang sama, abaikan
            # (tidak peduli jenis kendaraannya)
            if existing_plate == plate_number and existing_second == current_second:
                self.status_bar.config(text=f"Plat {plate_number} sudah diproses dalam detik yang sama, diabaikan")
                return
            
        if not self.tarifs:
            messagebox.showerror("Error", "Tidak ada tarif yang tersedia!")
            return
            
        # Get selected tarif
        selected_index = self.tarif_combo.current()
        if selected_index < 0:
            messagebox.showerror("Error", "Silakan pilih jenis kendaraan!")
            return
            
        tarif_id = self.tarifs[selected_index]['id']
        tarif_name = self.tarifs[selected_index]['kelompok_kendaraan']
        tarif_amount = float(self.tarifs[selected_index]['harga'])
        
        # Process transaction via API
        try:
            self.status_bar.config(text=f"Memproses transaksi untuk plat {plate_number}...")
            
            # Gunakan jenis kendaraan yang terdeteksi untuk memilih tarif OTOMATIS
            vehicle_name = self.detected_vehicle["name"]
            tarif_name = self.vehicle_to_tarif.get(vehicle_name, "Mobil")
            
            # Auto-select tarif berdasarkan jenis kendaraan yang terdeteksi
            tarif_selected = False
            for idx, tarif in enumerate(self.tarifs):
                if tarif["kelompok_kendaraan"] == tarif_name:
                    self.tarif_combo.current(idx)
                    tarif_selected = True
                    print(f"🤖 AUTO-SELECT: {vehicle_name} -> Tarif {tarif_name}")
                    break
            
            if not tarif_selected:
                print(f"⚠️ Tarif untuk {vehicle_name} tidak ditemukan, menggunakan default")
                self.tarif_combo.current(0)  # Default ke tarif pertama
            
            # Format plat nomor untuk consistency dengan database
            # Plat nomor dari deteksi OCR berisi "B 5432 KRI 5 0728", perlu diambil hanya "B 5432 KRI"
            # Juga menangani kasus seperti "S9106 HL" -> "S 9106 HL" untuk kecocokan dengan database
            
            # Coba berbagai format yang mungkin ada di database untuk meningkatkan peluang kecocokan
            possible_formats = []
            
            # Format 1: Format asli dari pendeteksian (mungkin sudah cocok dengan database)
            original_plate = plate_number.strip()
            possible_formats.append(original_plate)
            
            # Format 2: Hapus semua spasi
            clean_plate = plate_number.replace(" ", "")
            possible_formats.append(clean_plate)
            
            # Format 3: Format standar dengan spasi (misalnya "B 1234 CD")
            # Cek apakah karakter pertama adalah huruf (kode wilayah)
            if len(clean_plate) > 0 and clean_plate[0].isalpha():
                # Cek jika ada 2 huruf di awal (seperti AA 3454 LP)
                if len(clean_plate) > 1 and clean_plate[1].isalpha():
                    prefix = clean_plate[0:2]
                    remaining = clean_plate[2:]
                else:
                    # Jika karakter pertama adalah huruf, format dengan pola "S 9106 HL"
                    prefix = clean_plate[0]
                    remaining = clean_plate[1:]
                
                # Cari batas antara angka dan huruf
                digit_part = ""
                letter_part = ""
                for i, char in enumerate(remaining):
                    if char.isdigit():
                        digit_part += char
                    else:
                        letter_part = remaining[i:]
                        break
                
                if digit_part and letter_part:
                    standard_format = f"{prefix} {digit_part} {letter_part}"
                    possible_formats.append(standard_format)
                    # Juga tambahkan format tanpa spasi antara kode wilayah dan angka
                    possible_formats.append(f"{prefix}{digit_part} {letter_part}")
            
            # Format 4: Jika ada spasi, coba format standar
            plate_parts = plate_number.split()
            if len(plate_parts) >= 3:  # Pastikan minimal ada 3 bagian
                standard_format = f"{plate_parts[0]} {plate_parts[1]} {plate_parts[2]}"
                possible_formats.append(standard_format)
                # Tanpa spasi antara kode wilayah dan angka
                possible_formats.append(f"{plate_parts[0]}{plate_parts[1]} {plate_parts[2]}")
            
            # Hapus duplikat
            possible_formats = list(dict.fromkeys(possible_formats))
            print(f"Kemungkinan format plat nomor: {possible_formats}")
            
            # Pertama, coba validasi dengan semua format yang mungkin
            formatted_plate = original_plate  # Default ke format asli
            api_success = False
            
            # Coba semua format plat nomor yang mungkin hingga salah satu berhasil
            for plate_format in possible_formats:
                try:
                    validate_url = f"{self.api_url}/validate-plate-vehicle"
                    validate_data = {
                        'plat_nomor': plate_format,
                        'kelompok_kendaraan': vehicle_name
                    }
                    
                    print(f"🔍 MENCOBA FORMAT: {plate_format}")
                    response = requests.post(validate_url, json=validate_data, timeout=2)
                    
                    if response.status_code == 200:
                        print(f"✅ PLAT NOMOR DITEMUKAN dengan format: {plate_format}")
                        formatted_plate = plate_format
                        
                        # Ambil data dari API
                        response_data = response.json()
                        if 'data' in response_data:
                            self.current_user_data = response_data['data']
                        else:
                            self.current_user_data = response_data
                            
                        api_success = True
                        break  # Keluar dari loop jika berhasil
                    elif response.status_code == 403:
                        # Kendaraan tidak cocok, tapi plat nomor ditemukan
                        # Ini bisa jadi format plat nomor yang benar tapi kendaraan salah
                        print(f"⚠️ Format plat nomor cocok: {plate_format}, tapi kelompok kendaraan tidak sesuai")
                        formatted_plate = plate_format
                        
                        try:
                            error_data = response.json()
                            print(f"  Terdeteksi: {error_data.get('detected_vehicle', vehicle_name)}")
                            print(f"  Terdaftar: {error_data.get('registered_vehicle', 'Unknown')}")
                            print(f"Detail error: {error_data}")
                            
                            # Sesuaikan kelompok kendaraan dengan yang ada di database
                            registered_vehicle = error_data.get('registered_vehicle')
                            if registered_vehicle:
                                vehicle_name = registered_vehicle  # Gunakan kendaraan dari database
                                
                                # Update combo box sesuai dengan kelompok kendaraan yang benar
                                for idx, tarif in enumerate(self.tarifs):
                                    if tarif["kelompok_kendaraan"] == registered_vehicle:
                                        self.tarif_combo.current(idx)
                                        break
                            
                            # Coba lagi dengan kendaraan yang benar
                            validate_data['kelompok_kendaraan'] = vehicle_name
                            response = requests.post(validate_url, json=validate_data, timeout=2)
                            
                            if response.status_code == 200:
                                print(f"✅ VALIDASI BERHASIL dengan format: {plate_format} dan kendaraan: {vehicle_name}")
                                formatted_plate = plate_format
                                
                                # Ambil data dari API
                                response_data = response.json()
                                if 'data' in response_data:
                                    self.current_user_data = response_data['data']
                                else:
                                    self.current_user_data = response_data
                                    
                                api_success = True
                                break  # Keluar dari loop jika berhasil
                        except Exception as e:
                            print(f"Error saat mencoba menyesuaikan kendaraan: {str(e)}")
                except Exception as e:
                    print(f"Error saat mencoba format {plate_format}: {str(e)}")
                    
            # Jika semua format gagal, gunakan format standar untuk pelaporan
            if not api_success:
                print(f"Mencari plat nomor: {formatted_plate}")
                
                # Coba validasi ganda melalui API (plat nomor + kelompok kendaraan)
                try:
                    # Gunakan endpoint API POST baru untuk validasi ganda
                    validate_url = f"{self.api_url}/validate-plate-vehicle"
                    validate_data = {
                        'plat_nomor': formatted_plate,
                        'kelompok_kendaraan': vehicle_name
                    }
                    
                    print(f"🔍 VALIDASI GANDA:")
                    print(f"  Plat nomor: {formatted_plate}")
                    print(f"  Kelompok kendaraan: {vehicle_name}")
                    print(f"  URL API: {validate_url}")
                    
                    response = requests.post(validate_url, json=validate_data, timeout=2)
                    
                    # Log untuk debugging
                    print(f"API Status: {response.status_code}")
                    
                    if response.status_code == 200:
                        print(f"✅ API berhasil memvalidasi plat: {formatted_plate} sebagai {vehicle_name}")
                        # Tambahkan data yang diterima dari API
                        response_data = response.json()
                        print(f"API Response: {response_data}")
                        
                        # Simpan data user dari API jika ada - format baru
                        if 'data' in response_data:
                            self.current_user_data = response_data['data']
                        else:
                            self.current_user_data = response_data
                        api_success = True
                    elif response.status_code == 403:
                        # Kelompok kendaraan tidak sesuai
                        print(f"❌ KELOMPOK KENDARAAN TIDAK SESUAI:")
                        try:
                            error_data = response.json()
                            print(f"  Terdeteksi: {error_data.get('detected_vehicle', vehicle_name)}")
                            print(f"  Terdaftar: {error_data.get('registered_vehicle', 'Unknown')}")
                            print(f"Detail error: {error_data}")
                            
                            # Cobalah menggunakan jenis kendaraan yang terdaftar di database
                            registered_vehicle = error_data.get('registered_vehicle')
                            if registered_vehicle:
                                print(f"🔄 Mencoba dengan kelompok kendaraan dari database: {registered_vehicle}")
                                vehicle_name = registered_vehicle
                                
                                # Update combo box sesuai dengan kelompok kendaraan yang benar
                                for idx, tarif in enumerate(self.tarifs):
                                    if tarif["kelompok_kendaraan"] == registered_vehicle:
                                        self.tarif_combo.current(idx)
                                        tarif_amount = float(self.tarifs[idx]['harga'])
                                        break
                                
                                # Coba sekali lagi dengan kendaraan yang benar
                                validate_data['kelompok_kendaraan'] = vehicle_name
                                second_response = requests.post(validate_url, json=validate_data, timeout=2)
                                
                                if second_response.status_code == 200:
                                    print(f"✅ Validasi kedua berhasil dengan kendaraan dari database")
                                    response_data = second_response.json()
                                    if 'data' in response_data:
                                        self.current_user_data = response_data['data']
                                    else:
                                        self.current_user_data = response_data
                                    api_success = True
                                else:
                                    # Jika masih gagal, tambahkan ke history dengan status khusus
                                    status = "KENDARAAN SALAH"
                                    self.add_to_history(formatted_plate, vehicle_name, tarif_amount, status)
                                    return  # Stop processing
                            else:
                                # Tambahkan ke history dengan status khusus
                                status = "KENDARAAN SALAH"
                                self.add_to_history(formatted_plate, vehicle_name, tarif_amount, status)
                                return  # Stop processing
                        except Exception as e:
                            print(f"Error saat menyesuaikan kendaraan: {str(e)}")
                            status = "KENDARAAN SALAH"
                            self.add_to_history(formatted_plate, vehicle_name, tarif_amount, status)
                            return
                        api_success = False
                    elif response.status_code == 404:
                        print(f"❌ PLAT NOMOR TIDAK TERDAFTAR: {formatted_plate}")
                        try:
                            error_data = response.json()
                            print(f"Detail error: {error_data}")
                            
                            # Tambahkan ke history dengan status tidak terdaftar
                            status = "TIDAK TERDAFTAR"
                            self.add_to_history(formatted_plate, vehicle_name, tarif_amount, status)
                            return  # Stop processing
                        except:
                            pass
                        api_success = False
                    else:
                        print(f"❌ API error untuk plat: {formatted_plate}, status: {response.status_code}")
                        try:
                            error_data = response.json()
                            print(f"Detail error: {error_data}")
                        except:
                            pass
                        api_success = False
                except Exception as e:
                    api_success = False
                    print(f"Error saat memanggil API: {str(e)}")
            
            # Hanya gunakan API untuk validasi
            if api_success:
                is_valid = True
                print("Plat nomor valid dari API")
                
                # Sistem Pay Later - Transaksi tetap lanjut meskipun saldo tidak mencukupi
                if hasattr(self, 'current_user_data') and self.current_user_data:
                    try:
                        current_saldo = float(self.current_user_data.get('saldo', 0))
                        if current_saldo < tarif_amount:
                            # Saldo tidak mencukupi - sistem pay later aktif
                            kekurangan = tarif_amount - current_saldo
                            print(f"💳 SISTEM PAY LATER: Saldo tidak mencukupi")
                            print(f"Saldo saat ini: Rp {current_saldo:,.0f}")
                            print(f"Tarif dibutuhkan: Rp {tarif_amount:,.0f}")
                            print(f"Kekurangan: Rp {kekurangan:,.0f}")
                            print(f"Saldo akan menjadi negatif: Rp {current_saldo - tarif_amount:,.0f}")
                            print("✅ Transaksi dilanjutkan dengan sistem pay later")
                        else:
                            print(f"💰 Saldo mencukupi: Rp {current_saldo:,.0f}")
                    except (ValueError, TypeError):
                        # Jika ada error parsing saldo, lanjutkan saja
                        print("⚠️ Tidak dapat memeriksa saldo, transaksi tetap dilanjutkan")
                        pass
            else:
                is_valid = False
                print("Plat nomor tidak valid atau API tidak tersedia")
            
            if is_valid:
                # Process transaction
                try:
                    # Cari tarif ID berdasarkan jenis kendaraan
                    tarif_id = 1  # default
                    for tarif in self.tarifs:
                        if tarif['kelompok_kendaraan'].lower() == tarif_name.lower():
                            tarif_id = tarif['id']
                            break
                    
                    transaction_response = requests.post(
                        f"{self.api_url}/transactions/plate",
                        json={
                            "plat_nomor": formatted_plate,
                            "jenis_kendaraan": tarif_name
                        },
                        timeout=2
                    )
                    transaction_success = transaction_response.status_code == 200
                except Exception as e:
                    print(f"Transaction API error: {str(e)}")
                    transaction_success = False
                
                # Proses transaksi hanya melalui API
                if transaction_success:
                    result = transaction_response.json()
                    
                    # Cek apakah transaksi berhasil atau gagal
                    if result.get('success', False):
                        status = "SUKSES"
                        
                        # Ambil data dari response API
                        if 'data' in result:
                            user_data = result['data']['user']
                            tarif_data = result['data']['tarif']
                            transaction_data = result['data']['transaction']
                            
                            nama = user_data.get('nama_lengkap', 'N/A')
                            saldo_baru = float(result['data'].get('current_balance', user_data.get('saldo', 0)))
                            saldo_lama = float(result['data'].get('previous_balance', 0))
                            
                            self.status_bar.config(text=f"✅ TRANSAKSI BERHASIL: {formatted_plate}")
                            
                            # Tambahkan informasi user
                            user_info = f"Nama: {nama}\nSaldo Sebelum: Rp {saldo_lama:,.0f}\nSaldo Sesudah: Rp {saldo_baru:,.0f}".replace(",", ".")
                            
                            # Simpan data untuk notifikasi palang terbuka
                            self.current_user_data['previous_balance'] = saldo_lama
                            self.current_user_data['current_balance'] = saldo_baru
                            
                            # Cek jika saldo tinggal sedikit (warning)
                            if saldo_baru < 20000:  # Warning jika saldo < Rp 20.000
                                warning_msg = f"⚠️ PERINGATAN SALDO RENDAH!\n\nSaldo tersisa: Rp {saldo_baru:,.0f}\nSilakan top up segera.".replace(",", ".")
                                messagebox.showwarning("💰 Saldo Rendah", warning_msg)
                        else:
                            # Fallback jika format response tidak sesuai
                            self.status_bar.config(text=f"✅ TRANSAKSI BERHASIL: {formatted_plate}")
                            # Buat data user minimal
                            self.current_user_data = {
                                'nama_lengkap': 'Pengguna',
                                'saldo': tarif_amount,
                                'previous_balance': tarif_amount * 2  # Perkiraan saldo awal
                            }
                        
                        # Tandai plat nomor ini sudah diproses
                        transaction_key = f"{formatted_plate}_{vehicle_name}"
                        current_time = time.time()
                        self.processed_transactions[transaction_key] = {
                            "plate": formatted_plate,
                            "vehicle": vehicle_name,
                            "time": current_time,
                            "second": int(current_time)  # Simpan juga detik untuk pengecekan cepat
                        }
                        
                        # Kirim notifikasi FCM ke mobile app
                        self.send_fcm_notification(user_data, transaction_data, saldo_lama, saldo_baru)
                        
                        # Buka palang pintu
                        self.open_gate()
                        
                        # Tampilkan indikator palang terbuka
                        self.show_gate_indicator(True)
                    else:
                        # Transaksi gagal (saldo tidak cukup, dll)
                        status = "SALDO HABIS" if "insufficient" in result.get('message', '').lower() or "saldo" in result.get('message', '').lower() else "GAGAL"
                        error_msg = result.get('message', 'Transaksi gagal')
                        
                        # Pesan khusus untuk saldo habis
                        if status == "SALDO HABIS":
                            # Ambil data saldo dari response jika ada
                            current_balance = 0
                            required_amount = tarif_amount
                            
                            if 'data' in result and 'user' in result['data']:
                                current_balance = float(result['data']['user'].get('saldo', 0))
                            
                            saldo_msg = f"💳 SALDO TIDAK MENCUKUPI!\n\n"
                            saldo_msg += f"Saldo saat ini: Rp {current_balance:,.0f}\n"
                            saldo_msg += f"Tarif yang dibutuhkan: Rp {required_amount:,.0f}\n"
                            saldo_msg += f"Kekurangan: Rp {max(0, required_amount - current_balance):,.0f}\n\n"
                            saldo_msg += f"Silakan top up saldo Anda terlebih dahulu."
                            saldo_msg = saldo_msg.replace(",", ".")
                            
                            self.status_bar.config(text=f"💳 SALDO HABIS: {formatted_plate}")
                            messagebox.showerror("💳 SALDO TIDAK MENCUKUPI", saldo_msg)
                        else:
                            self.status_bar.config(text=f"❌ TRANSAKSI GAGAL: {error_msg}")
                            messagebox.showerror("❌ TRANSAKSI GAGAL", error_msg)
                else:
                    status = "GAGAL"
                    error_msg = "API transaksi tidak tersedia"
                    self.status_bar.config(text=f"Transaksi gagal: {error_msg}")
                    messagebox.showerror("Error", f"Transaksi gagal: {error_msg}")
            else:
                status = "TIDAK TERDAFTAR"
                self.status_bar.config(text=f"Plat nomor {formatted_plate} tidak terdaftar")
                
            # Add to history regardless of status
            self.add_to_history(formatted_plate, tarif_name, tarif_amount, status)
            
        except Exception as e:
            self.status_bar.config(text=f"Error: {str(e)}")
            messagebox.showerror("Error", f"Terjadi kesalahan: {str(e)}")

    def start_background_animation(self):
        """Start the particle animation in the background"""
        if hasattr(self, 'main_bg') and isinstance(self.main_bg, tk.Canvas):
            width = self.root.winfo_width()
            height = self.root.winfo_height()
            self.particle_animation = ParticleAnimation(
                self.main_bg, width, height, num_particles=50, color=self.colors["accent_light"]
            )
            self.particle_animation.start()
    
    def toggle_animation(self):
        """Toggle the background animation on/off"""
        if self.animation_active:
            if hasattr(self, 'particle_animation'):
                self.particle_animation.stop()
            self.animation_active = False
        else:
            self.start_background_animation()
            self.animation_active = True
    
    def toggle_theme(self):
        """Toggle between dark and light mode"""
        if self.dark_mode:
            # Switch to light mode
            self.colors = {
                "bg_dark": "#f0f2f5",
                "bg_medium": "#e1e5eb",
                "bg_light": "#ffffff",
                "accent": "#1282a2",
                "accent_light": "#0abdc6",
                "success": "#2ecc71",
                "warning": "#f39c12",
                "error": "#e74c3c",
                "text_light": "#ffffff",
                "text_dark": "#333333"
            }
            self.dark_mode = False
        else:
            # Switch to dark mode
            self.colors = {
                "bg_dark": "#0a1128",
                "bg_medium": "#001f54",
                "bg_light": "#034078",
                "accent": "#1282a2",
                "accent_light": "#0abdc6",
                "success": "#2ecc71",
                "warning": "#f39c12",
                "error": "#e74c3c",
                "text_light": "#fefcfb",
                "text_dark": "#333333"
            }
            self.dark_mode = True
        
        # Restart the application to apply theme
        messagebox.showinfo("Tema Berubah", "Aplikasi akan restart untuk menerapkan tema baru.")
        self.root.after(500, self.restart_app)
    
    def restart_app(self):
        """Restart the application"""
        self.on_closing(restart=True)
        python = sys.executable
        os.execl(python, python, *sys.argv)
    
    def show_frame_settings(self):
        """Show frame skipping settings dialog"""
        settings_window = tk.Toplevel(self.root)
        settings_window.title("Pengaturan Frame Skipping")
        settings_window.geometry("500x400")
        settings_window.configure(bg=self.colors["bg_medium"])
        settings_window.resizable(False, False)
        
        # Create header
        header = tk.Frame(settings_window, bg=self.colors["accent"], padx=20, pady=10)
        header.pack(fill="x")
        
        tk.Label(header, text="⚙️ PENGATURAN FRAME SKIPPING", font=self.subtitle_font, 
               fg=self.colors["text_light"], bg=self.colors["accent"]).pack()
        
        # Main content
        content_frame = tk.Frame(settings_window, bg=self.colors["bg_medium"], padx=20, pady=20)
        content_frame.pack(fill="both", expand=True)
        
        # Adaptive skipping toggle
        adaptive_frame = tk.Frame(content_frame, bg=self.colors["bg_medium"], pady=10)
        adaptive_frame.pack(fill="x")
        
        adaptive_var = tk.BooleanVar(value=self.adaptive_skipping)
        adaptive_check = tk.Checkbutton(adaptive_frame, text="Adaptive Frame Skipping", 
                                      variable=adaptive_var, font=self.label_font,
                                      fg=self.colors["text_light"], bg=self.colors["bg_medium"],
                                      selectcolor=self.colors["bg_dark"],
                                      activebackground=self.colors["bg_medium"],
                                      activeforeground=self.colors["text_light"])
        adaptive_check.pack(anchor="w")
        
        tk.Label(adaptive_frame, text="Otomatis menyesuaikan frame skipping berdasarkan performa", 
               font=self.status_font, fg=self.colors["text_light"], bg=self.colors["bg_medium"]).pack(anchor="w", pady=(0, 10))
        
        # Frame skip slider
        skip_frame = tk.Frame(content_frame, bg=self.colors["bg_medium"], pady=10)
        skip_frame.pack(fill="x")
        
        tk.Label(skip_frame, text="Frame Skip:", font=self.label_font, 
               fg=self.colors["text_light"], bg=self.colors["bg_medium"]).pack(anchor="w")
        
        skip_var = tk.IntVar(value=self.frame_skip)
        skip_slider = tk.Scale(skip_frame, from_=0, to=10, orient="horizontal", 
                             variable=skip_var, font=self.status_font,
                             fg=self.colors["text_light"], bg=self.colors["bg_medium"],
                             activebackground=self.colors["accent"],
                             troughcolor=self.colors["bg_dark"],
                             highlightthickness=0, bd=0)
        skip_slider.pack(fill="x", pady=5)
        
        tk.Label(skip_frame, text="0 = Proses semua frame, 10 = Proses setiap 11 frame", 
               font=self.status_font, fg=self.colors["text_light"], bg=self.colors["bg_medium"]).pack(anchor="w")
        
        # Target FPS slider
        fps_frame = tk.Frame(content_frame, bg=self.colors["bg_medium"], pady=10)
        fps_frame.pack(fill="x")
        
        tk.Label(fps_frame, text="Target FPS:", font=self.label_font, 
               fg=self.colors["text_light"], bg=self.colors["bg_medium"]).pack(anchor="w")
        
        fps_var = tk.IntVar(value=self.target_fps)
        fps_slider = tk.Scale(fps_frame, from_=5, to=30, orient="horizontal", 
                            variable=fps_var, font=self.status_font,
                            fg=self.colors["text_light"], bg=self.colors["bg_medium"],
                            activebackground=self.colors["accent"],
                            troughcolor=self.colors["bg_dark"],
                            highlightthickness=0, bd=0)
        fps_slider.pack(fill="x", pady=5)
        
        tk.Label(fps_frame, text="Target FPS untuk adaptive skipping", 
               font=self.status_font, fg=self.colors["text_light"], bg=self.colors["bg_medium"]).pack(anchor="w")
        
        # Current performance info
        perf_frame = tk.Frame(content_frame, bg=self.colors["bg_dark"], padx=15, pady=15, bd=1, relief="raised")
        perf_frame.pack(fill="x", pady=15)
        
        tk.Label(perf_frame, text=f"Current FPS: {self.current_fps:.1f}", font=self.label_font, 
               fg=self.colors["accent_light"], bg=self.colors["bg_dark"]).pack(anchor="w")
        
        tk.Label(perf_frame, text=f"Current Frame Skip: {self.frame_skip}", font=self.label_font, 
               fg=self.colors["accent_light"], bg=self.colors["bg_dark"]).pack(anchor="w")
        
        # Buttons
        button_frame = tk.Frame(settings_window, bg=self.colors["bg_medium"], pady=15)
        button_frame.pack(fill="x")
        
        # Apply button
        apply_btn = RoundedButton(button_frame, width=150, height=40, corner_radius=20,
                                color=self.colors["success"], text="TERAPKAN", 
                                command=lambda: self.apply_frame_settings(
                                    adaptive_var.get(), skip_var.get(), fps_var.get(), settings_window))
        apply_btn.pack(side="left", padx=20)
        
        # Cancel button
        cancel_btn = RoundedButton(button_frame, width=150, height=40, corner_radius=20,
                                 color=self.colors["error"], text="BATAL", 
                                 command=settings_window.destroy)
        cancel_btn.pack(side="right", padx=20)
        
    def apply_frame_settings(self, adaptive, skip, fps, window):
        """Apply frame skipping settings"""
        self.adaptive_skipping = adaptive
        self.frame_skip = skip
        self.target_fps = fps
        self.status_bar.config(text=f"Pengaturan frame skipping diperbarui: Adaptive={adaptive}, Skip={skip}, Target FPS={fps}")
        window.destroy()
        
    def reset_history(self):
        """
        Reset histori transaksi dan statistik
        """
        # Konfirmasi reset
        confirm = messagebox.askyesno("Konfirmasi", "Apakah Anda yakin ingin menghapus semua histori transaksi?")
        if not confirm:
            return
            
        # Reset treeview
        for item in self.history_tree.get_children():
            self.history_tree.delete(item)
            
        # Reset processed transactions
        self.processed_transactions = {}
        
        # Reset statistik
        self.stats = {
            "total_detections": 0,
            "successful_transactions": 0,
            "failed_transactions": 0,
            "unregistered_plates": 0,
            "vehicle_types": {"Mobil": 0, "Bus": 0, "Truk": 0},
            "start_time": datetime.now()
        }
        
        # Update status
        self.status_bar.config(text="Histori transaksi dan statistik telah direset")
        messagebox.showinfo("Reset Berhasil", "Histori transaksi dan statistik telah dihapus")
        
        
    def show_statistics(self):
        """Show statistics in a popup window"""
        stats_window = tk.Toplevel(self.root)
        stats_window.title("Statistik Sistem")
        stats_window.geometry("600x550")
        stats_window.configure(bg=self.colors["bg_medium"])
        
        # Calculate uptime
        uptime = datetime.now() - self.stats["start_time"]
        hours, remainder = divmod(uptime.seconds, 3600)
        minutes, seconds = divmod(remainder, 60)
        uptime_str = f"{hours}h {minutes}m {seconds}s"
        
        # Create header
        header = tk.Frame(stats_window, bg=self.colors["accent"], padx=20, pady=10)
        header.pack(fill="x")
        
        tk.Label(header, text="📊 STATISTIK SISTEM", font=self.subtitle_font, 
               fg=self.colors["text_light"], bg=self.colors["accent"]).pack()
        
        # Create notebook for tabs
        notebook = ttk.Notebook(stats_window)
        notebook.pack(fill="both", expand=True, padx=10, pady=10)
        
        # Tab 1: General Stats
        general_tab = tk.Frame(notebook, bg=self.colors["bg_medium"], padx=20, pady=20)
        notebook.add(general_tab, text="Statistik Umum")
        
        # Stats grid
        stats_grid = tk.Frame(general_tab, bg=self.colors["bg_medium"])
        stats_grid.pack(pady=20)
        
        # Define stats with icons
        stats_data = [
            ("🕒 Waktu Aktif:", uptime_str),
            ("🔍 Total Deteksi:", str(self.stats["total_detections"])),
            ("✅ Transaksi Sukses:", str(self.stats["successful_transactions"])),
            ("❌ Transaksi Gagal:", str(self.stats["failed_transactions"])),
            ("⚠️ Plat Tidak Terdaftar:", str(self.stats["unregistered_plates"])),
            ("🚗 Total Kendaraan:", str(sum(self.stats["vehicle_types"].values())))
        ]
        
        # Add stats to grid
        for i, (label, value) in enumerate(stats_data):
            tk.Label(stats_grid, text=label, font=self.label_font, fg=self.colors["text_light"],
                   bg=self.colors["bg_medium"], anchor="w").grid(row=i, column=0, sticky="w", pady=10)
            
            tk.Label(stats_grid, text=value, font=self.label_font, fg=self.colors["accent_light"],
                   bg=self.colors["bg_medium"], anchor="e").grid(row=i, column=1, sticky="e", pady=10, padx=20)
        
        # Success rate calculation
        total_trans = self.stats["successful_transactions"] + self.stats["failed_transactions"]
        success_rate = (self.stats["successful_transactions"] / total_trans * 100) if total_trans > 0 else 0
        
        # Create progress bar for success rate
        rate_frame = tk.Frame(general_tab, bg=self.colors["bg_medium"], pady=10)
        rate_frame.pack(fill="x")
        
        tk.Label(rate_frame, text="Tingkat Keberhasilan:", font=self.label_font,
               fg=self.colors["text_light"], bg=self.colors["bg_medium"]).pack(anchor="w")
        
        progress_frame = tk.Frame(rate_frame, bg=self.colors["bg_dark"], padx=2, pady=2)
        progress_frame.pack(fill="x", pady=5)
        
        success_bar_bg = tk.Frame(progress_frame, bg=self.colors["bg_dark"], height=25)
        success_bar_bg.pack(fill="x")
        
        success_bar = tk.Frame(success_bar_bg, bg=self.colors["success"], height=25)
        success_bar.place(relwidth=success_rate/100, relheight=1)
        
        percentage = tk.Label(success_bar_bg, text=f"{success_rate:.1f}%", font=self.label_font,
                           fg=self.colors["text_light"], bg=self.colors["bg_dark"])
        percentage.place(relx=0.5, rely=0.5, anchor="center")
        
        # Tab 2: Vehicle Statistics
        vehicle_tab = tk.Frame(notebook, bg=self.colors["bg_medium"], padx=20, pady=20)
        notebook.add(vehicle_tab, text="Statistik Kendaraan")
        
        # Vehicle stats header
        vehicle_header = tk.Label(vehicle_tab, text="Klasifikasi Kendaraan", 
                               font=self.subtitle_font, fg=self.colors["text_light"], 
                               bg=self.colors["bg_medium"])
        vehicle_header.pack(pady=10)
        
        # Vehicle stats grid
        vehicle_grid = tk.Frame(vehicle_tab, bg=self.colors["bg_medium"])
        vehicle_grid.pack(pady=20)
        
        # Vehicle emojis
        vehicle_emojis = {
            "Mobil": "🚗",
            "Bus": "🚌",
            "Truk": "🚚"
        }
        
        # Vehicle colors
        vehicle_colors = {
            "Mobil": "#4CAF50",  # Green
            "Bus": "#1E88E5",    # Blue
            "Truk": "#F44336"    # Red
        }
        
        # Add vehicle stats to grid with color indicators
        total_vehicles = sum(self.stats["vehicle_types"].values())
        
        for i, (vehicle_name, count) in enumerate(self.stats["vehicle_types"].items()):
            # Add color indicator
            color_indicator = tk.Frame(vehicle_grid, width=15, height=15, bg=vehicle_colors.get(vehicle_name, "#FFFFFF"))
            color_indicator.grid(row=i, column=0, padx=(0, 10), pady=10)
            
            # Add vehicle name with emoji
            emoji = vehicle_emojis.get(vehicle_name, "🚗")
            label = f"{emoji} {vehicle_name}:"
            tk.Label(vehicle_grid, text=label, font=self.label_font, fg=self.colors["text_light"],
                   bg=self.colors["bg_medium"], anchor="w").grid(row=i, column=1, sticky="w", pady=10)
            
            # Add count
            tk.Label(vehicle_grid, text=str(count), font=self.label_font, fg=self.colors["accent_light"],
                   bg=self.colors["bg_medium"], anchor="e").grid(row=i, column=2, sticky="e", pady=10, padx=(20, 10))
            
            # Add percentage
            percentage = (count / total_vehicles * 100) if total_vehicles > 0 else 0
            tk.Label(vehicle_grid, text=f"({percentage:.1f}%)", font=self.label_font, fg=self.colors["text_light"],
                   bg=self.colors["bg_medium"], anchor="w").grid(row=i, column=3, sticky="w", pady=10)
            
        # Vehicle distribution visualization (bar chart)
        chart_frame = tk.Frame(vehicle_tab, bg=self.colors["bg_dark"], padx=10, pady=10)
        chart_frame.pack(fill="x", pady=20)
        
        chart_title = tk.Label(chart_frame, text="Distribusi Jenis Kendaraan", 
                             font=self.label_font, fg=self.colors["text_light"], 
                             bg=self.colors["bg_dark"])
        chart_title.pack(pady=5)
        
        # Create bars
        bars_frame = tk.Frame(chart_frame, bg=self.colors["bg_dark"], height=200)
        bars_frame.pack(fill="x", pady=10)
        
        # Add bars
        max_count = max(self.stats["vehicle_types"].values()) if self.stats["vehicle_types"] else 1
        bar_width = 80
        
        for i, (vehicle_name, count) in enumerate(self.stats["vehicle_types"].items()):
            # Container for each bar
            bar_container = tk.Frame(bars_frame, bg=self.colors["bg_dark"])
            bar_container.place(x=i*100 + 30, y=0, width=bar_width, height=180)
            
            # Calculate bar height
            bar_height = int((count / max_count) * 150) if max_count > 0 else 0
            if bar_height < 5 and count > 0:
                bar_height = 5  # Minimum height for visibility
            
            # Bar
            bar = tk.Frame(bar_container, bg=vehicle_colors.get(vehicle_name, "#FFFFFF"))
            bar.place(relx=0.5, y=160-bar_height, width=bar_width, height=bar_height, anchor="n")
            
            # Label
            tk.Label(bar_container, text=vehicle_emojis.get(vehicle_name, "🚗"), 
                   font=("Segoe UI Emoji", 16), bg=self.colors["bg_dark"], 
                   fg=self.colors["text_light"]).place(relx=0.5, y=165, anchor="n")
            
            # Count
            tk.Label(bar_container, text=str(count), font=self.status_font, 
                   bg=self.colors["bg_dark"], fg=vehicle_colors.get(vehicle_name, "#FFFFFF")).place(
                   relx=0.5, y=160-bar_height-15, anchor="s")
        
        # Close button for window
        close_btn = RoundedButton(stats_window, width=150, height=40, corner_radius=20,
                                color=self.colors["accent"], text="TUTUP", 
                                command=stats_window.destroy)
        close_btn.pack(pady=20)
    
    def add_to_history(self, plate, tarif_name, tarif_amount, status):
        waktu_sekarang = datetime.now().strftime("%H:%M:%S")
        tarif_formatted = f"Rp {tarif_amount:,.0f}".replace(",", ".")
        
        # Different colors for different statuses with more vibrant colors
        tag = status.lower().replace(" ", "_")
        self.history_tree.tag_configure("sukses", background="#2ecc71", foreground="#ffffff")  # Hijau terang dengan teks putih
        self.history_tree.tag_configure("gagal", background="#e74c3c", foreground="#ffffff")   # Merah terang dengan teks putih
        self.history_tree.tag_configure("tidak_terdaftar", background="#f39c12", foreground="#ffffff")  # Oranye dengan teks putih
        self.history_tree.tag_configure("saldo_habis", background="#8e44ad", foreground="#ffffff")  # Ungu untuk saldo habis
        self.history_tree.tag_configure("kendaraan_salah", background="#e67e22", foreground="#ffffff")  # Oranye gelap untuk kendaraan salah
        
        
        # Tambahkan ke histori dengan status yang sesuai
        self.history_tree.insert("", 0, values=(waktu_sekarang, plate, tarif_name, tarif_formatted, status), 
                                tags=(tag.lower(),))
        
        # Update statistics
        self.stats["total_detections"] += 1
        if status.lower() == "sukses":
            self.stats["successful_transactions"] += 1
        elif status.lower() == "gagal":
            self.stats["failed_transactions"] += 1
        elif status.lower() == "saldo habis":
            self.stats["failed_transactions"] += 1  # Saldo habis dihitung sebagai transaksi gagal
        elif status.lower() == "tidak terdaftar":
            self.stats["unregistered_plates"] += 1
        elif status.lower() == "kendaraan salah":
            self.stats["failed_transactions"] += 1  # Kendaraan salah dihitung sebagai transaksi gagal

    def toggle_buttons(self, enable):
        state = "normal" if enable else "disabled"
        
        # Update rounded buttons
        self.btn_image.set_state(state)
        self.btn_video.set_state(state)
        self.btn_webcam.set_state(state)
        self.btn_stop.set_state("normal" if not enable else "disabled")
        self.emergency_stop_btn.set_state("normal" if not enable else "disabled")
        
        # Update dropdown
        self.tarif_combo.config(state="readonly" if enable else tk.DISABLED)

    def display_image(self, img_cv, label_widget, max_width):
        if img_cv is None:
            return
        
        # Resize logic
        h, w = img_cv.shape[:2]
        ratio = max_width / w if w > max_width else 1
        new_width = int(w * ratio)
        new_height = int(h * ratio)
        resized_img = cv2.resize(img_cv, (new_width, new_height))

        img_rgb = cv2.cvtColor(resized_img, cv2.COLOR_BGR2RGB)
        pil_image = Image.fromarray(img_rgb)
        tk_image = ImageTk.PhotoImage(pil_image)
        
        label_widget.config(image=tk_image)
        label_widget.image = tk_image

    def on_closing(self, restart=False):
        # Stop processing and animation
        self.stop_processing()
        if hasattr(self, 'particle_animation'):
            self.particle_animation.stop()
            
        # If not restarting, destroy the window
        if not restart:
            self.root.destroy()
            
    def toggle_fullscreen(self):
        """Toggle fullscreen mode"""
        is_fullscreen = self.root.attributes('-fullscreen')
        self.root.attributes('-fullscreen', not is_fullscreen)

if __name__ == "__main__":
    root = tk.Tk()
    # Pastikan ukuran awal jendela cukup besar
    root.geometry("1280x720")
    # Maximize window sebelum aplikasi dimulai
    root.update()
    app = TollGateApp(root)
    # Pastikan window benar-benar dimaksimalkan setelah aplikasi dimulai
    root.update()
    root.mainloop()
