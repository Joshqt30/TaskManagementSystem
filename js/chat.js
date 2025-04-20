document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('chat-form');
    const chatMessages = document.getElementById('chat-messages');
    const messageInput = chatForm.querySelector('input[name="content"]');
    const fileInput = chatForm.querySelector('input[name="file"]');
    const receiverId = chatForm.querySelector('input[name="receiver_id"]').value;
  
    if (chatForm) {
      chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('send_message.php', {
          method: 'POST',
          body: formData
        })
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          if (data.error) {
            alert('Error sending message: ' + data.error);
            return;
          }
  
          // Emit the message via Socket.IO
          const messageData = {
            user_id: window.currentUserId,
            contact_id: receiverId,
            content: messageInput.value.trim(),
            file_path: data.file_path || ''
          };
          console.log('Emitting send_message:', messageData);
          socket.emit('send_message', messageData);
  
          // Clear the input fields
          messageInput.value = '';
          fileInput.value = '';
        })
        .catch(error => {
          console.error('Error sending message:', error);
          alert('Error sending message: ' + error.message);
        });
      });
    }
  });