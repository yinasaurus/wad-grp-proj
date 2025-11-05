const chatbotButton = document.getElementById("chatbot-button");
const chatbotBox = document.getElementById("chatbot-box");
const chatbotMessages = document.getElementById("chatbot-messages");
const chatbotSend = document.getElementById("chatbot-send");
const chatbotText = document.getElementById("chatbot-text");
const chatbotClose = document.getElementById("chatbot-close");

// toggle open/close
chatbotButton.addEventListener("click", () => {
  const isOpen = chatbotBox.classList.contains("open");
  if (isOpen) {
    closeChat();
  } else {
    openChat();
  }
});

chatbotClose.addEventListener("click", closeChat);

function openChat() {
  chatbotBox.classList.add("open");
  chatbotButton.textContent = "❌"; // change icon
}

function closeChat() {
  chatbotBox.classList.remove("open");
  chatbotButton.textContent = "💬";
}

// send message
async function sendMessage() {
  const text = chatbotText.value.trim();
  if (!text) return;

  addMessage("user", text);
  chatbotText.value = "";

  const typing = addMessage("bot", "...");
  try {
    const res = await fetch("api/chatbot.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ message: text })
    });

    const reply = await res.text();
    typing.remove();
    addMessage("bot", reply || "No response");
  } catch (err) {
    typing.remove();
    addMessage("bot", "Error connecting to chatbot.");
  }
}

chatbotSend.addEventListener("click", sendMessage);
chatbotText.addEventListener("keypress", e => {
  if (e.key === "Enter") sendMessage();
});

function addMessage(sender, text) {
  const msg = document.createElement("div");
  msg.classList.add("message", sender);
  msg.textContent = text;
  chatbotMessages.appendChild(msg);
  chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
  return msg;
}

