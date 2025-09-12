#!/usr/bin/env python3
"""
Simple test server untuk testing aplikasi toll gate
"""
from http.server import HTTPServer, BaseHTTPRequestHandler
import json
import urllib.parse

class TollAPIHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        parsed_path = urllib.parse.urlparse(self.path)
        path = parsed_path.path
        
        print(f"GET request: {path}")
        
        if path == '/api/tarifs':
            self.send_response(200)
            self.send_header('Content-Type', 'application/json')
            self.send_header('Access-Control-Allow-Origin', '*')
            self.end_headers()
            
            response_data = {
                "success": True,
                "data": [
                    {"id": 1, "kelompok_kendaraan": "Motor", "harga": 5000},
                    {"id": 2, "kelompok_kendaraan": "Mobil", "harga": 10000},
                    {"id": 3, "kelompok_kendaraan": "Bus", "harga": 15000},
                    {"id": 4, "kelompok_kendaraan": "Truk", "harga": 20000}
                ]
            }
            
            self.wfile.write(json.dumps(response_data).encode())
            
        elif path.startswith('/api/validate-plate/'):
            plate_number = path.split('/')[-1]
            plate_number = urllib.parse.unquote(plate_number)
            
            self.send_response(200)
            self.send_header('Content-Type', 'application/json')
            self.send_header('Access-Control-Allow-Origin', '*')
            self.end_headers()
            
            # Test plates
            test_plates = {
                "B 5432 KRI": {"user_id": 3, "username": "test1", "nama_lengkap": "Pengguna Test Satu", "saldo": 200000},
                "B 1234 XYZ": {"user_id": 1, "username": "johndoe", "nama_lengkap": "John Doe", "saldo": 100000},
                "D 5678 ABC": {"user_id": 2, "username": "janedoe", "nama_lengkap": "Jane Doe", "saldo": 50000}
            }
            
            if plate_number in test_plates:
                user_data = test_plates[plate_number]
                response_data = {
                    "success": True,
                    "valid": True,
                    "message": "Plat nomor terdaftar",
                    "plate": plate_number,
                    "data": user_data
                }
            else:
                response_data = {
                    "success": False,
                    "valid": False,
                    "message": "Plat nomor tidak terdaftar",
                    "plate": plate_number
                }
                self.send_response(404)
                self.send_header('Content-Type', 'application/json')
                self.send_header('Access-Control-Allow-Origin', '*')
                self.end_headers()
            
            self.wfile.write(json.dumps(response_data).encode())
            
        elif path == '/api/health':
            self.send_response(200)
            self.send_header('Content-Type', 'application/json')
            self.send_header('Access-Control-Allow-Origin', '*')
            self.end_headers()
            
            response_data = {
                "success": True,
                "message": "Test API is running",
                "timestamp": "2025-09-09T00:00:00Z"
            }
            
            self.wfile.write(json.dumps(response_data).encode())
            
        else:
            self.send_response(404)
            self.end_headers()
            self.wfile.write(b'Not Found')
    
    def do_POST(self):
        parsed_path = urllib.parse.urlparse(self.path)
        path = parsed_path.path
        
        print(f"POST request: {path}")
        
        if path == '/api/transactions/plate':
            content_length = int(self.headers['Content-Length'])
            post_data = self.rfile.read(content_length)
            data = json.loads(post_data.decode())
            
            plate_number = data.get('plat_nomor')
            vehicle_type = data.get('jenis_kendaraan')
            
            print(f"Transaction request for plate: {plate_number}, vehicle: {vehicle_type}")
            
            # Simulate transaction
            self.send_response(200)
            self.send_header('Content-Type', 'application/json')
            self.send_header('Access-Control-Allow-Origin', '*')
            self.end_headers()
            
            response_data = {
                "success": True,
                "message": "Transaction successful",
                "data": {
                    "user": {
                        "nama_lengkap": "Test User",
                        "saldo": 150000
                    },
                    "tarif": {
                        "kelompok_kendaraan": vehicle_type,
                        "harga": 10000
                    },
                    "transaction": {
                        "status": "SUCCESS"
                    },
                    "previous_balance": 160000,
                    "current_balance": 150000
                }
            }
            
            self.wfile.write(json.dumps(response_data).encode())
        else:
            self.send_response(404)
            self.end_headers()
            self.wfile.write(b'Not Found')

def run_server(port=8080):
    server_address = ('127.0.0.1', port)
    httpd = HTTPServer(server_address, TollAPIHandler)
    print(f"Starting test server on http://127.0.0.1:{port}")
    print("Available endpoints:")
    print("  GET  /api/health")
    print("  GET  /api/tarifs")
    print("  GET  /api/validate-plate/{plate_number}")
    print("  POST /api/transactions/plate")
    print("\nPress Ctrl+C to stop the server")
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        print("\nServer stopped.")
        httpd.server_close()

if __name__ == '__main__':
    run_server()
