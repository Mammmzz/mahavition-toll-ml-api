#!/usr/bin/env python3
import sys
import cv2
import json
from ultralytics import YOLO
import easyocr

def detect_plate_yolo_and_ocr(image_path, yolo_model, ocr_reader):
    """
    Mendeteksi plat nomor dari path gambar dan mengembalikan hasil dalam format JSON
    """
    try:
        # Baca gambar
        img = cv2.imread(image_path)
        if img is None:
            return json.dumps({
                "success": False,
                "error": "Tidak dapat membaca gambar"
            })

        # Deteksi plat nomor dengan YOLO
        results = yolo_model(img)
        
        if not results or len(results[0].boxes) == 0:
            return json.dumps({
                "success": False,
                "error": "Tidak ada plat nomor terdeteksi"
            })
        
        # Ambil kotak hasil deteksi dengan confidence tertinggi
        boxes = results[0].boxes
        conf_scores = boxes.conf.tolist()
        
        if not conf_scores:
            return json.dumps({
                "success": False,
                "error": "Tidak ada plat nomor terdeteksi dengan confidence yang memadai"
            })
        
        # Ambil indeks box dengan confidence tertinggi
        max_conf_idx = conf_scores.index(max(conf_scores))
        box = boxes.xyxy[max_conf_idx].tolist()  # x1, y1, x2, y2
        confidence = conf_scores[max_conf_idx]
        
        # Extract plate ROI
        x1, y1, x2, y2 = map(int, box)
        plate_roi = img[y1:y2, x1:x2]
        
        # Baca teks dengan EasyOCR
        plate_text = "Tidak terbaca"
        if plate_roi is not None and plate_roi.size > 0:
            ocr_result = ocr_reader.readtext(plate_roi)
            
            if ocr_result:
                # Gabungkan semua teks terdeteksi
                plate_text = " ".join([text[1] for text in ocr_result])
                # Bersihkan teks
                plate_text = plate_text.replace(" ", "").upper()
        
        return json.dumps({
            "success": True,
            "plate_number": plate_text,
            "confidence": confidence,
            "bbox": box
        })
        
    except Exception as e:
        return json.dumps({
            "success": False,
            "error": str(e)
        })

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "Gambar tidak disediakan"}))
        sys.exit(1)
    
    image_path = sys.argv[1]
    
    # Load model
    yolo_model = YOLO('license_plate_detector.pt')
    ocr_reader = easyocr.Reader(['id'], gpu=False)
    
    result = detect_plate_yolo_and_ocr(image_path, yolo_model, ocr_reader)
    print(result)
