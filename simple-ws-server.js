const WebSocket = require('ws');
const http = require('http');

// Create HTTP server
const server = http.createServer((req, res) => {
  res.writeHead(200, {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
    'Access-Control-Allow-Headers': 'Content-Type'
  });
  res.end('Simple WebSocket Server Running\n');
});

// Create WebSocket server
const wss = new WebSocket.Server({ 
  server,
  path: '/websocket'
});

console.log('🚀 Simple WebSocket Server starting...');

wss.on('connection', function connection(ws, req) {
  console.log('✅ New WebSocket connection from:', req.connection.remoteAddress);
  
  ws.on('message', function incoming(message) {
    console.log('📨 Received:', message.toString());
    
    // Echo message back to all connected clients
    wss.clients.forEach(function each(client) {
      if (client.readyState === WebSocket.OPEN) {
        client.send(JSON.stringify({
          type: 'message',
          data: message.toString(),
          timestamp: new Date().toISOString()
        }));
      }
    });
  });
  
  ws.on('close', function() {
    console.log('❌ WebSocket connection closed');
  });
  
  // Send welcome message
  ws.send(JSON.stringify({
    type: 'connected',
    message: 'Welcome to Simple WebSocket Server',
    timestamp: new Date().toISOString()
  }));
});

// Start server
const PORT = 6001;
server.listen(PORT, '127.0.0.1', () => {
  console.log(`✅ Simple WebSocket Server running on http://127.0.0.1:${PORT}`);
  console.log(`📡 WebSocket endpoint: ws://127.0.0.1:${PORT}/websocket`);
});
