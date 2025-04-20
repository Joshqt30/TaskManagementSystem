const http = require('http').createServer();
const io = require('socket.io')(http, {
  cors: {
    origin: "http://localhost",
    methods: ["GET", "POST"]
  }
});

io.on('connection', (socket) => {
  console.log('A user connected:', socket.id);

  socket.on('join_conversation', (data) => {
    const { user_id, contact_id } = data;
    const room = [user_id, contact_id].sort().join('_');
    socket.join(room);
    console.log(`User ${user_id} joined room ${room}`);
  });

  socket.on('send_message', (data) => {
    const { user_id, contact_id, content, file_path } = data;
    const room = [user_id, contact_id].sort().join('_');
    console.log(`Broadcasting message to room ${room}:`, data);
    io.to(room).emit('new_message', {
      sender_id: user_id,
      content: content,
      file_path: file_path,
      created_at: new Date().toISOString()
    });
  });

  socket.on('disconnect', (reason) => {
    console.log('A user disconnected:', socket.id, 'Reason:', reason);
  });
});

http.listen(3000, () => {
  console.log('WebSocket server running on port 3000');
});