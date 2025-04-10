<!-- resources/views/components/chat.blade.php -->
@props(['user', 'currentUser', 'messages'])

<div class="flex flex-col h-[500px]">
    <div class="flex items-center">
        <h1 class="text-lg font-semibold mr-2">{{ $user->name }}</h1>
        <span id="user-status-{{ $user->id }}" class="inline-block h-2 w-2 rounded-full bg-gray-400"></span>
    </div>

    <!-- Messages -->
    <div id="message-container" class="overflow-y-auto p-4 mt-3 flex-grow border-t border-gray-200">
        <div class="space-y-4" id="messages-list">
            @foreach($messages as $message)
                <div class="mb-4 {{ $message->sender_id === $currentUser->id ? 'text-right' : '' }}">
                    <div class="{{ $message->sender_id === $currentUser->id ? 'bg-indigo-500 text-white' : 'bg-gray-200 text-gray-800' }} inline-block px-5 py-2 rounded-lg">
                        <p>{{ $message->message }}</p>
                        <span class="text-[10px]">{{ \Carbon\Carbon::parse($message->created_at)->format('g:i A') }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Message Input -->
    <div class="border-t pt-4">
        <form id="message-form">
            @csrf
            <div class="flex items-center">
                <input
                    id="new-message"
                    type="text"
                    class="flex-1 border p-3 rounded-lg"
                    placeholder="Type your message here..."
                />
                <button type="submit" class="ml-2 bg-indigo-500 text-white p-3 rounded-lg shadow hover:bg-indigo-600 transition duration-300 flex items-center justify-center">
                    <i class="fas fa-paper-plane"></i>
                    <span class="ml-2">Send</span>
                </button>
            </div>
        </form>
    </div>

    <small id="typing-indicator" class="text-gray-600 mt-5" style="display: none;">
        {{ $user->name }} is typing...
    </small>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const userId = {{ $user->id }};
        const currentUserId = {{ $currentUser->id }};
        let typingTimer;
        const messageContainer = document.getElementById('message-container');
        const messagesList = document.getElementById('messages-list');
        const messageForm = document.getElementById('message-form');
        const messageInput = document.getElementById('new-message');
        const typingIndicator = document.getElementById('typing-indicator');

        // Scroll to bottom of message container
        const scrollToBottom = () => {
            messageContainer.scrollTo({
                top: messageContainer.scrollHeight,
                behavior: "smooth",
            });
        };

        // Format time
        const formatTime = (datetime) => {
            const date = new Date(datetime);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        };

        // Add a new message to the chat
        const addMessage = (message, isSentByMe = null) => {
            // If isSentByMe is not provided, determine it from message data
            if (isSentByMe === null) {
                isSentByMe = parseInt(message.sender_id) === currentUserId;
            }

            const messageDiv = document.createElement('div');
            messageDiv.className = `mb-4 ${isSentByMe ? 'text-right' : ''}`;

            const messageContent = document.createElement('div');
            messageContent.className = `${isSentByMe ? 'bg-indigo-500 text-white' : 'bg-gray-200 text-gray-800'} inline-block px-5 py-2 rounded-lg`;

            const messagePara = document.createElement('p');
            messagePara.textContent = message.message || message.text;

            const timeSpan = document.createElement('span');
            timeSpan.className = 'text-[10px]';
            timeSpan.textContent = message.created_at ? formatTime(message.created_at) : formatTime(new Date());

            messageContent.appendChild(messagePara);
            messageContent.appendChild(timeSpan);
            messageDiv.appendChild(messageContent);
            messagesList.appendChild(messageDiv);

            scrollToBottom();
        };

        // Initialize - fetch messages once at load
        const fetchMessages = () => {
            fetch(`/messages/${userId}`)
                .then(response => response.json())
                .then(data => {
                    // Clear current messages
                    messagesList.innerHTML = '';

                    // Add each message
                    data.forEach(message => {
                        addMessage(message);
                    });

                    scrollToBottom();
                })
                .catch(error => console.error('Failed to fetch messages:', error));
        };

        // Send a message
        const sendMessage = (messageText) => {
            if (messageText.trim() === '') return;

            // Create a temporary message object for immediate display
            const tempMessage = {
                message: messageText,
                sender_id: currentUserId,
                created_at: new Date()
            };

            // Add the message immediately to the UI
            addMessage(tempMessage, true);

            // Clear the input
            messageInput.value = '';

            // Send to server
            fetch(`/messages/${userId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({ message: messageText })
            })
            .then(response => response.json())
            .catch(error => console.error('Failed to send message:', error));
        };

        // Event listeners
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            sendMessage(messageInput.value);
        });

        messageInput.addEventListener('input', function() {
            // Send typing event through Echo
            if (window.Echo) {
                window.Echo.private(`chat.${userId}`).whisper('typing', {
                    userID: currentUserId
                });
            }
        });

        // Initialize
        fetchMessages();
        scrollToBottom();

        // Set up Echo listeners if Echo is available
        if (window.Echo) {
            // Presence channel for online status
            window.Echo.join('presence.chat')
                .here(users => {
                    const isUserOnline = users.some(user => user.id === userId);
                    document.getElementById(`user-status-${userId}`).className =
                        `inline-block h-2 w-2 rounded-full ${isUserOnline ? 'bg-green-500' : 'bg-gray-400'}`;
                })
                .joining(user => {
                    if (user.id === userId) {
                        document.getElementById(`user-status-${userId}`).className = 'inline-block h-2 w-2 rounded-full bg-green-500';
                    }
                })
                .leaving(user => {
                    if (user.id === userId) {
                        document.getElementById(`user-status-${userId}`).className = 'inline-block h-2 w-2 rounded-full bg-gray-400';
                    }
                });

            // Listen for new messages on the private channel
            window.Echo.private(`chat.${currentUserId}`)
                .listen('MessageSent', (e) => {
                    console.log('New message received:', e.message);

                    // Add message directly to UI without fetching all messages again
                    addMessage(e.message, false);
                })
                .listenForWhisper('typing', (response) => {
                    if (response.userID === userId) {
                        typingIndicator.style.display = 'block';

                        if (typingTimer) {
                            clearTimeout(typingTimer);
                        }

                        typingTimer = setTimeout(() => {
                            typingIndicator.style.display = 'none';
                        }, 1000);
                    }
                });
        }
    });
</script>