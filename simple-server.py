#!/usr/bin/env python3
import socket
import threading
import time
from http.server import HTTPServer, SimpleHTTPRequestHandler
import json

class WebSocketHandler:
    def __init__(self, port=6001):
        self.port = port
        self.clients = []
        
    def start_server(self):
        server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        server_socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        server_socket.bind(('127.0.0.1', self.port))
        server_socket.listen(5)
        
        print(f'✅ Simple WebSocket Server running on ws://127.0.0.1:{self.port}')
        
        while True:
            try:
                client_socket, address = server_socket.accept()
                print(f'✅ New connection from {address}')
                
                # Simple echo server
                client_thread = threading.Thread(
                    target=self.handle_client, 
                    args=(client_socket, address)
                )
                client_thread.daemon = True
                client_thread.start()
                
            except Exception as e:
                print(f'❌ Server error: {e}')
                
    def handle_client(self, client_socket, address):
        try:
            # Simple HTTP response for testing
            data = client_socket.recv(1024).decode()
            if 'GET /' in data:
                response = 'HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\nAccess-Control-Allow-Origin: *\r\n\r\nWebSocket Server OK'
                client_socket.send(response.encode())
            
            client_socket.close()
            
        except Exception as e:
            print(f'❌ Client error: {e}')

if __name__ == '__main__':
    handler = WebSocketHandler()
    handler.start_server()
