# AI-Powered Toll Gate System

A comprehensive Python-based solution for automated toll collection using computer vision and machine learning. This system integrates YOLO v8 for vehicle and license plate detection with EasyOCR for optical character recognition, providing real-time toll processing capabilities.

## Features

- **Real-time Vehicle & License Plate Detection**: Powered by YOLOv8 for high-accuracy detection
- **Multi-class Vehicle Classification**: Identifies vehicle types (Car/Bus/Truck) for dynamic toll calculation
- **Automatic Number Plate Recognition (ANPR)**: Uses EasyOCR for robust text extraction from license plates
- **Toll Gate Simulation**: Complete toll booth simulation with gate control and transaction processing
- **REST API Integration**: Seamless connection with Laravel backend for transaction processing
- **Real-time Processing**: Supports webcam, RTSP streams, and video file inputs
- **GUI Dashboard**: User-friendly interface with real-time visualization and controls

## System Requirements

- **Python**: 3.8 or higher
- **Operating Systems**: Windows 10/11, Linux (Ubuntu 20.04+), macOS 12+
- **Hardware**:
  - CPU: Intel Core i5 or equivalent (minimum)
  - RAM: 8GB+ (16GB recommended for optimal performance)
  - GPU: NVIDIA GPU with CUDA support (optional but recommended for faster inference)
  - Webcam or IP camera (for real-time detection)
  - Internet connection (for API communication)

## Installation

### Prerequisites
1. **Python Installation**:
   - Download and install Python 3.8+ from [python.org](https://www.python.org/downloads/)
   - During installation, ensure "Add Python to PATH" is checked
   - Verify installation: `python --version`

2. **Clone the Repository**:
   ```bash
   git clone https://github.com/Mammmzz/mahavition-toll-ml-api.git
   cd mahavition-toll-ml-api/plat_nomor_backend
   ```

### Setup on Windows
```cmd
# Create and activate virtual environment
python -m venv venv
venv\Scripts\activate

# Install dependencies
pip install -r requirements.txt
```

### Setup on Linux/macOS
```bash
# Create and activate virtual environment
python3 -m venv venv
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt

# Additional system dependencies (Ubuntu/Debian)
sudo apt-get update
sudo apt-get install -y libgl1-mesa-glx
```

### Model Files
Ensure these model files are in the project directory:
- `license_plate_detector.pt`: Custom YOLO model for license plate detection
- `vehicle_classifier.pt`: Vehicle classification model
- `yolov8n.pt`: Base YOLOv8 nano model (will be auto-downloaded if not present)

## Usage

### Toll Gate Application
Run the main toll gate application with GUI:
```bash
python toll_gate_app.py
```

#### Features:
- Real-time video processing from webcam or RTSP stream
- Automatic vehicle detection and classification
- License plate recognition
- Toll calculation based on vehicle type
- Transaction logging and API integration
- Visual feedback and alerts

### API Mode
For integration with other systems or batch processing:
```bash
# Single image processing
python plate_recognition_api.py --image path/to/image.jpg

# Process video file
python plate_recognition_api.py --video path/to/video.mp4

# Webcam stream
python plate_recognition_api.py --webcam
```

### Command Line Arguments
```
--webcam           Use default webcam as video source
--rtsp URL         Use RTSP stream as video source
--video PATH       Process video file
--image PATH       Process single image
--api-url URL      Backend API URL (default: http://localhost:8000)
--api-key KEY      API authentication key
--debug            Enable debug mode
```

## Project Structure

```
plat_nomor_backend/
├── toll_gate_app.py          # Main toll gate application with GUI
├── plate_recognition_api.py  # Command-line API for integration
├── vehicle_detection.py      # Vehicle detection and classification
├── license_plate_ocr.py      # License plate text recognition
├── api_client.py            # API communication with Laravel backend
├── utils/
│   ├── config.py            # Configuration settings
│   ├── logger.py            # Logging utilities
│   └── helpers.py           # Helper functions
├── models/
│   ├── license_plate_detector.pt  # Custom YOLO model
│   └── vehicle_classifier.pt      # Vehicle classification model
├── assets/
│   ├── sounds/              # Audio notifications
│   └── images/              # UI assets
├── requirements.txt          # Python dependencies
└── README.md                # This file
```

## System Architecture

1. **Vehicle Detection**:
   - YOLOv8 model detects vehicles in the input stream
   - Classifies vehicles into categories (Car/Bus/Truck)
   - Tracks vehicles across frames for consistent processing

2. **License Plate Recognition**:
   - Detects license plates using custom YOLO model
   - Applies perspective correction and preprocessing
   - Uses EasyOCR for robust text extraction
   - Implements post-processing for improved accuracy

3. **Toll Processing**:
   - Calculates toll based on vehicle type and time of day
   - Communicates with Laravel backend via REST API
   - Processes transactions and updates user balances
   - Triggers gate mechanism and notifications

4. **User Interface**:
   - Real-time video display with detection overlays
   - System status and notifications
   - Configuration panel for system settings

## Integration with Backend

The system integrates with the Laravel backend using REST API. Ensure the backend is running and accessible before starting the toll gate application.

### API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/validate-plate` | POST | Validate detected plate number |
| `/api/process-toll` | POST | Process toll transaction |
| `/api/transaction/status` | GET | Check transaction status |

### Environment Variables
Create a `.env` file in the project root with the following variables:
```
API_BASE_URL=http://localhost:8000/api
API_KEY=your_api_key_here
CAMERA_SOURCE=0  # 0 for default webcam, RTSP URL, or file path
DEBUG_MODE=False
```

## Troubleshooting

### Common Issues

1. **CUDA/GPU Issues**
   - Ensure you have compatible NVIDIA drivers and CUDA toolkit installed
   - Verify PyTorch with CUDA support is installed: `python -c "import torch; print(torch.cuda.is_available())"`
   - If using CPU-only mode, expect slower performance

2. **Webcam Access**
   - On Linux, ensure user has permission to access video devices
   - Try running with `sudo` if permission denied
   - Verify camera index (usually 0 for built-in webcam)

3. **API Connection**
   - Check if backend server is running and accessible
   - Verify API URL and authentication key in `.env`
   - Test API connectivity using curl or Postman

4. **Model Loading**
   - Ensure model files are in the correct directory
   - Check file permissions
   - Verify model compatibility with installed library versions

5. **Dependency Issues**
   - Always use the provided `requirements.txt`
   - Create a fresh virtual environment if encountering conflicts
   - Update pip before installing dependencies

## Performance Tuning

For optimal performance:
1. **Hardware Acceleration**:
   - Use a CUDA-compatible GPU for faster inference
   - Allocate sufficient RAM (16GB+ recommended)

2. **Camera Settings**:
   - Use appropriate resolution (720p recommended)
   - Ensure good lighting conditions
   - Position camera at optimal angle and distance

3. **Software Optimization**:
   - Reduce frame processing rate if needed
   - Adjust confidence thresholds in `config.py`
   - Enable hardware acceleration in OpenCV if available

## License

This project is proprietary software. All rights reserved.

## Support

For technical support, please contact the development team or open an issue in the repository.**
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
