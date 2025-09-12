import cv2
import easyocr
from ultralytics import YOLO
import tkinter as tk
from tkinter import filedialog, ttk, font
from PIL import Image, ImageTk
from datetime import datetime
import threading

# --- FUNGSI DETEKSI (DIPERBARUI UNTUK MENERIMA FRAME) ---
def detect_plate_yolo_and_ocr(source_img, yolo_model, ocr_reader):
    """
    Mendeteksi plat nomor dari gambar (baik path file maupun frame cv2).
    """
    if isinstance(source_img, str):
        img = cv2.imread(source_img)
    else:
        img = source_img

    if img is None:
        return "Gagal memuat gambar", "N/A", None, None, 0

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
        cv2.rectangle(img, (x1, y1), (x2, y2), (0, 255, 0), 3)

        ocr_result = ocr_reader.readtext(best_plate_roi, detail=1, paragraph=False)

        plate_number = "Tidak terbaca"
        expiry_date = "N/A"

        if ocr_result:
            if len(ocr_result) == 1:
                raw_plate = ocr_result[0][1].upper()
                cleaned_plate = "".join([c for c in raw_plate if c.isalnum() or c == ' '])
                plate_number = " ".join(cleaned_plate.split())
            elif len(ocr_result) > 1:
                lowest_block = max(ocr_result, key=lambda res: (res[0][0][1] + res[0][2][1]) / 2)
                raw_date = lowest_block[1]
                expiry_date = "".join([c for c in raw_date if c.isdigit() or c in '.-'])
                plate_parts = [res for res in ocr_result if res != lowest_block]
                if plate_parts:
                    plate_parts.sort(key=lambda p: p[0][0][0])
                    raw_plate = ' '.join([part[1] for part in plate_parts]).upper()
                    cleaned_plate = "".join([c for c in raw_plate if c.isalnum() or c == ' '])
                    plate_number = " ".join(cleaned_plate.split())

            if plate_number != "Tidak terbaca":
                 cv2.putText(img, plate_number, (x1, y1 - 10),
                            cv2.FONT_HERSHEY_SIMPLEX, 1.2, (36, 255, 12), 3)

        return plate_number, expiry_date, img, best_plate_roi, best_confidence

    return "Plat tidak terdeteksi", "N/A", img, None, 0

# --- APLIKASI GUI DENGAN TKINTER (DIREFAKTOR BESAR) ---
class ParkingApp:
    def __init__(self, root):
        self.root = root
        self.root.title("Aplikasi Deteksi Plat Nomor (Gambar, Video, Webcam)")
        # Maximize window - cross platform
        try:
            self.root.state('zoomed')  # Windows
        except:
            self.root.attributes('-zoomed', True)  # Linux
        self.root.configure(bg="#2c3e50")
        self.root.protocol("WM_DELETE_WINDOW", self.on_closing)

        self.yolo_model = YOLO('license_plate_detector.pt')
        self.ocr_reader = easyocr.Reader(['id'], gpu=False)
        
        self.processing_active = False
        self.video_capture = None
        self.last_detected_plate = ""

        self.init_fonts_and_styles()
        self.create_widgets()
        self.update_clock()

    def init_fonts_and_styles(self):
        self.title_font = font.Font(family="Helvetica", size=16, weight="bold")
        self.label_font = font.Font(family="Arial", size=12)
        self.result_font = font.Font(family="Courier", size=28, weight="bold")
        self.date_font = font.Font(family="Courier", size=18)
        style = ttk.Style()
        style.theme_use("clam")
        style.configure("Treeview", rowheight=25, font=("Arial", 10))
        style.configure("Treeview.Heading", font=("Helvetica", 12, "bold"))

    def create_widgets(self):
        # ... (Header and Clock remains the same)
        header_frame = tk.Frame(self.root, bg="#34495e", height=50)
        header_frame.pack(fill="x")
        title_label = tk.Label(header_frame, text="Sistem Deteksi Plat Nomor", font=self.title_font, fg="white", bg="#34495e")
        title_label.pack(side="left", padx=10, pady=10)
        self.clock_label = tk.Label(header_frame, font=self.title_font, fg="white", bg="#34495e")
        self.clock_label.pack(side="right", padx=10, pady=10)

        main_frame = tk.Frame(self.root, bg="#2c3e50")
        main_frame.pack(fill="both", expand=True, padx=10, pady=10)
        main_frame.grid_columnconfigure(0, weight=3)
        main_frame.grid_columnconfigure(1, weight=2)
        main_frame.grid_rowconfigure(0, weight=1)

        left_frame = tk.Frame(main_frame, bg="#34495e")
        left_frame.grid(row=0, column=0, sticky="nsew", padx=(0, 5))
        self.image_label = tk.Label(left_frame, bg="#34495e", text="Pilih sumber media untuk memulai", fg="white", font=self.label_font)
        self.image_label.pack(fill="both", expand=True)

        right_frame = tk.Frame(main_frame, bg="#34495e")
        right_frame.grid(row=0, column=1, sticky="nsew", padx=(5, 0))

        # --- FRAME KONTROL BARU ---
        control_frame = tk.Frame(right_frame, bg="#34495e")
        control_frame.pack(fill="x", pady=10, padx=10)
        
        self.btn_image = tk.Button(control_frame, text="Pilih Gambar", command=self.process_image, font=self.label_font, bg="#2980b9", fg="white", relief="flat")
        self.btn_image.grid(row=0, column=0, sticky="ew", padx=2, pady=2)
        
        self.btn_video = tk.Button(control_frame, text="Pilih Video", command=self.start_video_processing, font=self.label_font, bg="#2980b9", fg="white", relief="flat")
        self.btn_video.grid(row=0, column=1, sticky="ew", padx=2, pady=2)

        self.btn_webcam = tk.Button(control_frame, text="Buka Webcam", command=self.start_webcam_processing, font=self.label_font, bg="#2980b9", fg="white", relief="flat")
        self.btn_webcam.grid(row=1, column=0, sticky="ew", padx=2, pady=2)

        self.btn_stop = tk.Button(control_frame, text="Stop", command=self.stop_processing, font=self.label_font, bg="#c0392b", fg="white", relief="flat", state=tk.DISABLED)
        self.btn_stop.grid(row=1, column=1, sticky="ew", padx=2, pady=2)
        
        control_frame.grid_columnconfigure(0, weight=1)
        control_frame.grid_columnconfigure(1, weight=1)

        # ... (Result and History frames remain mostly the same)
        result_frame = tk.Frame(right_frame, bg="#2c3e50", relief="sunken", borderwidth=2)
        result_frame.pack(fill="x", pady=10, padx=10)
        tk.Label(result_frame, text="PLAT NOMOR TERBACA:", font=self.label_font, fg="white", bg="#2c3e50").pack()
        self.result_plate_label = tk.Label(result_frame, text="-", font=self.result_font, fg="#f1c40f", bg="#2c3e50")
        self.result_plate_label.pack(pady=5)
        tk.Label(result_frame, text="TANGGAL KADALUWARSA:", font=self.label_font, fg="white", bg="#2c3e50").pack(pady=(10,0))
        self.expiry_date_label = tk.Label(result_frame, text="-", font=self.date_font, fg="#e74c3c", bg="#2c3e50")
        self.expiry_date_label.pack(pady=5)
        self.cropped_plate_label = tk.Label(result_frame, bg="#2c3e50")
        self.cropped_plate_label.pack(pady=10)
        self.confidence_label = tk.Label(result_frame, text="Keyakinan: - %", font=self.label_font, fg="white", bg="#2c3e50")
        self.confidence_label.pack(pady=5)
        
        history_frame = tk.Frame(right_frame, bg="#34495e")
        history_frame.pack(fill="both", expand=True, pady=10, padx=10)
        tk.Label(history_frame, text="Histori Deteksi", font=("Helvetica", 14, "bold"), fg="white", bg="#34495e").pack(pady=5)
        cols = ("Waktu", "Plat Nomor", "Tgl Kadaluwarsa", "Keyakinan (%)")
        self.history_tree = ttk.Treeview(history_frame, columns=cols, show="headings")
        for col in cols: self.history_tree.heading(col, text=col)
        self.history_tree.column("Waktu", width=100)
        self.history_tree.column("Plat Nomor", width=140)
        self.history_tree.column("Tgl Kadaluwarsa", width=120, anchor="center")
        self.history_tree.column("Keyakinan (%)", width=100, anchor="center")
        self.history_tree.pack(fill="both", expand=True)

        self.status_bar = tk.Label(self.root, text="Siap", bd=1, relief="sunken", anchor="w", fg="white", bg="#34495e")
        self.status_bar.pack(side="bottom", fill="x")

    def update_clock(self):
        now = datetime.now().strftime("%A, %d %B %Y | %H:%M:%S")
        self.clock_label.config(text=now)
        self.root.after(1000, self.update_clock)

    def process_image(self):
        if self.processing_active: return
        file_path = filedialog.askopenfilename(filetypes=[("Image Files", "*.jpg *.jpeg *.png")])
        if not file_path: return

        self.status_bar.config(text=f"Memproses gambar: {file_path.split('/')[-1]}...")
        self.root.update_idletasks()
        
        plate_number, expiry_date, img_hasil, img_potongan, confidence = detect_plate_yolo_and_ocr(
            file_path, self.yolo_model, self.ocr_reader
        )
        self.update_ui_results(plate_number, expiry_date, img_hasil, img_potongan, confidence)
        
        if plate_number != "Tidak terbaca" and plate_number != self.last_detected_plate:
            self.add_to_history(plate_number, expiry_date, confidence)
            self.last_detected_plate = plate_number

        self.status_bar.config(text="Selesai memproses gambar.")

    def start_video_processing(self):
        if self.processing_active: return
        file_path = filedialog.askopenfilename(filetypes=[("Video Files", "*.mp4 *.avi *.mov")])
        if not file_path: return
        self.start_processing_thread(file_path)

    def start_webcam_processing(self):
        if self.processing_active: return
        self.start_processing_thread(0)

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

        while self.processing_active:
            ret, frame = self.video_capture.read()
            if not ret:
                self.status_bar.config(text="Video selesai atau sumber tidak tersedia.")
                break
            
            plate_number, expiry_date, img_hasil, img_potongan, confidence = detect_plate_yolo_and_ocr(
                frame, self.yolo_model, self.ocr_reader
            )
            
            self.update_ui_results(plate_number, expiry_date, img_hasil, img_potongan, confidence)

            if plate_number != "Tidak terbaca" and plate_number != self.last_detected_plate:
                self.add_to_history(plate_number, expiry_date, confidence)
                self.last_detected_plate = plate_number
        
        if not self.processing_active: # If stopped by user
             self.status_bar.config(text="Pemrosesan dihentikan.")
        
        self.stop_processing()


    def stop_processing(self):
        self.processing_active = False
        if self.video_capture:
            self.video_capture.release()
            self.video_capture = None
        self.toggle_buttons(True)

    def update_ui_results(self, plate, date, processed_img, cropped_img, conf):
        self.result_plate_label.config(text=plate if plate else "-")
        self.expiry_date_label.config(text=date if date else "-")
        self.confidence_label.config(text=f"Keyakinan: {conf*100:.2f} %")

        if processed_img is not None:
            self.display_image(processed_img, self.image_label, max_width=800)
        if cropped_img is not None:
            self.display_image(cropped_img, self.cropped_plate_label, max_width=250)
        else:
            self.cropped_plate_label.config(image='')

    def add_to_history(self, plate, date, conf):
        waktu_sekarang = datetime.now().strftime("%H:%M:%S")
        self.history_tree.insert("", 0, values=(waktu_sekarang, plate, date, f"{conf*100:.2f}"))

    def toggle_buttons(self, enable):
        state = tk.NORMAL if enable else tk.DISABLED
        self.btn_image.config(state=state)
        self.btn_video.config(state=state)
        self.btn_webcam.config(state=state)
        self.btn_stop.config(state=tk.NORMAL if not enable else tk.DISABLED)

    def display_image(self, img_cv, label_widget, max_width):
        if img_cv is None: return
        
        # Resize logic
        h, w, _ = img_cv.shape
        ratio = max_width / w
        new_height = int(h * ratio)
        resized_img = cv2.resize(img_cv, (max_width, new_height))

        img_rgb = cv2.cvtColor(resized_img, cv2.COLOR_BGR2RGB)
        pil_image = Image.fromarray(img_rgb)
        tk_image = ImageTk.PhotoImage(pil_image)
        
        label_widget.config(image=tk_image)
        label_widget.image = tk_image

    def on_closing(self):
        self.stop_processing()
        self.root.destroy()

if __name__ == "__main__":
    root = tk.Tk()
    app = ParkingApp(root)
    root.mainloop()
