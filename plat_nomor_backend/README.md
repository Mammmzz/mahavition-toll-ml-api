# License Plate Detection System

A Python-based license plate detection and recognition system using YOLO v8 and EasyOCR. This application can detect and recognize Indonesian license plates from images and video streams.

## Features

- **License Plate Detection**: Uses YOLOv8 model for accurate plate detection
- **Text Recognition**: EasyOCR for extracting text from detected plates
- **GUI Application**: User-friendly Tkinter interface (`parkir_app.py`)
- **API Mode**: Command-line API for batch processing (`plate_recognition_api.py`)
- **Toll Gate System**: Advanced toll gate application (`toll_gate_app.py`)
- **Real-time Processing**: Support for webcam and video file processing

## Requirements

- Python 3.8 or higher
- Windows 10/11
- Webcam (optional, for real-time detection)

## Installation on Windows

### Step 1: Install Python
1. Download Python from [python.org](https://www.python.org/downloads/)
2. During installation, make sure to check "Add Python to PATH"
3. Verify installation by opening Command Prompt and running:
   ```cmd
   python --version
   ```

### Step 2: Clone the Repository
```cmd
git clone https://github.com/Mammmzz/python.git
cd python
```

### Step 3: Create Virtual Environment
```cmd
python -m venv venv
venv\Scripts\activate
```

### Step 4: Install Dependencies
```cmd
pip install -r requirements.txt
```

**Note**: The installation may take several minutes as it downloads PyTorch and other large dependencies.

### Step 5: Download Model Files
Make sure you have the following model files in the project directory:
- `license_plate_detector.pt` - Custom YOLO model for license plate detection
- `yolov8n.pt` - Base YOLOv8 nano model

## Usage

### GUI Application (Parking System)
Run the main parking application with GUI:
```cmd
python parkir_app.py
```

### Command Line API
For batch processing or integration with other systems:
```cmd
python plate_recognition_api.py path/to/image.jpg
```

### Toll Gate System
Run the advanced toll gate application:
```cmd
python toll_gate_app.py
```

## Project Structure

```
plat_nomor_backend/
├── parkir_app.py              # Main GUI application
├── plate_recognition_api.py   # Command-line API
├── toll_gate_app.py          # Toll gate system
├── requirements.txt          # Python dependencies
├── license_plate_detector.pt # Custom YOLO model
├── yolov8n.pt               # Base YOLO model
├── beep-401570.mp3          # Audio notification
├── img/                     # Image directory
└── venv/                    # Virtual environment
```

## How It Works

1. **Detection**: The system uses a custom-trained YOLOv8 model to detect license plates in images
2. **Recognition**: EasyOCR extracts text from the detected plate regions
3. **Processing**: The application processes the recognized text and displays results
4. **Storage**: Results can be logged with timestamps for record-keeping

## Troubleshooting

### Common Issues on Windows:

1. **"python is not recognized"**
   - Make sure Python is added to your system PATH
   - Try using `py` instead of `python`

2. **pip install fails**
   - Try upgrading pip: `python -m pip install --upgrade pip`
   - Use: `pip install -r requirements.txt --no-cache-dir`

3. **OpenCV camera issues**
   - Make sure your webcam is not being used by other applications
   - Try different camera indices (0, 1, 2) in the code

4. **CUDA/GPU errors**
   - The application will automatically fall back to CPU if CUDA is not available
   - For GPU acceleration, install CUDA toolkit from NVIDIA

### Performance Tips:
- For better performance, ensure you have adequate RAM (8GB+ recommended)
- Close unnecessary applications while running the detection system
- Use SSD storage for faster model loading

## License

This project is for educational and research purposes.

## Support

If you encounter any issues, please check the troubleshooting section above or create an issue in the repository.
