/**
 * RoboAIAPaths Chatbot Widget v2.0
 * Self-contained, embeddable chat widget.
 * Just include this script on any page:
 *   <script src="js/chatbot-widget.js"></script>
 *
 * Configure the API_URL below to point to your deployed FastAPI backend.
 */
(function () {
  "use strict";

  // ====== CONFIGURATION ======
  // Default relative API path (works for subdirectories and custom domains)
  let CHATBOT_API_URL = "api/chatbot_handler.php";
  
  // Fallback to live URL if opened directly from local disk (file:// protocol)
  if (window.location.protocol === 'file:') {
    CHATBOT_API_URL = "https://www.roboaiapaths.com/api/chatbot_handler.php";
  }

  // Session ID for tracking conversations
  const SESSION_ID = "session_" + Date.now() + "_" + Math.random().toString(36).substring(2, 8);

  // Pet mascot image path (relative to the page for local file:// & live support)
  const PET_IMAGE = "img/robo-pet.png";

  // ====== STYLES ======
  const WIDGET_STYLES = `
    .rba-chat-toggle {
      position: fixed;
      right: 25px;
      bottom: 25px;
      width: 72px;
      height: 72px;
      background: linear-gradient(135deg, #5b35d5, #8b5cf6);
      border-radius: 50%;
      border: none;
      cursor: pointer;
      box-shadow: 0 10px 30px rgba(91, 53, 213, 0.45);
      z-index: 99998;
      animation: rba-pulseGlow 2s infinite, rba-floatPet 3s ease-in-out infinite;
      overflow: hidden;
      padding: 6px;
      transition: transform 0.3s ease;
    }
    .rba-chat-toggle:hover {
      transform: scale(1.1);
    }
    .rba-chat-toggle img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      animation: rba-petWiggle 2.5s ease-in-out infinite;
    }
    @keyframes rba-pulseGlow {
      0% { box-shadow: 0 0 0 0 rgba(139,92,246,0.55); }
      70% { box-shadow: 0 0 0 18px rgba(139,92,246,0); }
      100% { box-shadow: 0 0 0 0 rgba(139,92,246,0); }
    }
    @keyframes rba-floatPet {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-8px); }
    }
    @keyframes rba-petWiggle {
      0%, 100% { transform: rotate(0deg) scale(1); }
      25% { transform: rotate(-5deg) scale(1.04); }
      50% { transform: rotate(5deg) scale(1.07); }
      75% { transform: rotate(-3deg) scale(1.04); }
    }

    .rba-chat-widget {
      position: fixed;
      right: 25px;
      bottom: 105px;
      width: 380px;
      height: 520px;
      max-height: calc(100vh - 125px);
      background: #ffffff;
      border-radius: 22px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      overflow: hidden;
      display: none;
      flex-direction: column;
      z-index: 99999;
      animation: rba-openChat 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
      font-family: 'Segoe UI', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    .rba-chat-widget * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    @keyframes rba-openChat {
      from { opacity: 0; transform: scale(0.82) translateY(30px); }
      to { opacity: 1; transform: scale(1) translateY(0); }
    }

    .rba-chat-header {
      min-height: 78px;
      background: linear-gradient(135deg, #21124d, #5b35d5, #8b5cf6);
      color: white;
      padding: 14px 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-shrink: 0;
    }
    .rba-chat-header-left {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .rba-pet-avatar {
      width: 52px;
      height: 52px;
      min-width: 52px;
      border-radius: 50%;
      background: white;
      padding: 4px;
      box-shadow: 0 0 18px rgba(255,255,255,0.45);
      animation: rba-headerFloat 2.8s ease-in-out infinite;
      overflow: hidden;
    }
    .rba-pet-avatar img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }
    @keyframes rba-headerFloat {
      0%, 100% { transform: translateY(0) rotate(0deg); }
      50% { transform: translateY(-5px) rotate(4deg); }
    }
    .rba-chat-title h3 {
      margin: 0;
      font-size: 16px;
      font-weight: 700;
      color: white;
      line-height: 1.2;
    }
    .rba-chat-title p {
      margin: 4px 0 0;
      font-size: 11.5px;
      color: rgba(255,255,255,0.85);
      font-weight: 400;
    }
    .rba-online-dot {
      display: inline-block;
      width: 8px;
      height: 8px;
      background: #22c55e;
      border-radius: 50%;
      margin-right: 5px;
      animation: rba-blinkDot 1.4s infinite;
    }
    @keyframes rba-blinkDot {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.35; }
    }
    .rba-chat-close {
      background: rgba(255,255,255,0.18);
      border: none;
      color: white;
      font-size: 22px;
      width: 34px;
      height: 34px;
      border-radius: 50%;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.2s;
      line-height: 1;
    }
    .rba-chat-close:hover {
      background: rgba(255,255,255,0.3);
    }

    .rba-chat-body {
      flex: 1;
      min-height: 0;
      padding: 15px;
      overflow-y: auto;
      background: radial-gradient(circle at top left, rgba(139,92,246,0.08), transparent 30%), #f8f8fc;
    }
    .rba-chat-body::-webkit-scrollbar {
      width: 5px;
    }
    .rba-chat-body::-webkit-scrollbar-thumb {
      background: #d1d5db;
      border-radius: 10px;
    }

    .rba-message {
      max-width: 86%;
      padding: 11px 14px;
      border-radius: 16px;
      margin-bottom: 10px;
      font-size: 13.5px;
      line-height: 1.5;
      white-space: pre-wrap;
      animation: rba-fadeSlide 0.3s ease;
      word-wrap: break-word;
    }
    @keyframes rba-fadeSlide {
      from { opacity: 0; transform: translateY(8px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .rba-bot-message {
      background: #ffffff;
      color: #1f2937;
      border: 1px solid #e5e7eb;
      border-bottom-left-radius: 4px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .rba-user-message {
      background: linear-gradient(135deg, #5b35d5, #8b5cf6);
      color: white;
      margin-left: auto;
      border-bottom-right-radius: 4px;
      box-shadow: 0 2px 8px rgba(91, 53, 213, 0.25);
    }

    .rba-quick-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 7px;
      margin-bottom: 12px;
    }
    .rba-quick-buttons button {
      border: 1.5px solid #8b5cf6;
      color: #5b35d5;
      background: white;
      border-radius: 22px;
      padding: 7px 13px;
      cursor: pointer;
      font-size: 12px;
      font-weight: 500;
      transition: all 0.25s;
      font-family: inherit;
    }
    .rba-quick-buttons button:hover {
      background: linear-gradient(135deg, #5b35d5, #8b5cf6);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(91,53,213,0.3);
    }

    .rba-typing {
      display: none;
      background: white;
      width: fit-content;
      padding: 10px 14px;
      border-radius: 16px;
      margin-bottom: 10px;
      border: 1px solid #e5e7eb;
    }
    .rba-typing span {
      display: inline-block;
      width: 7px;
      height: 7px;
      margin: 0 2px;
      background: #8b5cf6;
      border-radius: 50%;
      animation: rba-typingDots 1s infinite;
    }
    .rba-typing span:nth-child(2) { animation-delay: 0.15s; }
    .rba-typing span:nth-child(3) { animation-delay: 0.3s; }
    @keyframes rba-typingDots {
      0%, 80%, 100% { transform: translateY(0); opacity: 0.4; }
      40% { transform: translateY(-6px); opacity: 1; }
    }

    .rba-demo-box {
      padding: 10px 14px;
      background: linear-gradient(135deg, #fff7ed, #ffedd5);
      border-top: 1px solid #fed7aa;
      font-size: 12px;
      color: #7c2d12;
      flex-shrink: 0;
      line-height: 1.4;
    }

    .rba-chat-footer {
      padding: 12px;
      background: white;
      display: flex;
      gap: 8px;
      border-top: 1px solid #f0f0f0;
      flex-shrink: 0;
    }
    .rba-chat-footer input {
      flex: 1;
      padding: 11px 16px;
      border: 1.5px solid #e5e7eb;
      border-radius: 25px;
      outline: none;
      font-size: 14px;
      font-family: inherit;
      transition: border-color 0.2s;
      color: #1f2937;
      background: #fafafa;
    }
    .rba-chat-footer input:focus {
      border-color: #8b5cf6;
      background: white;
    }
    .rba-chat-footer input::placeholder {
      color: #9ca3af;
    }
    .rba-chat-footer button {
      background: linear-gradient(135deg, #5b35d5, #8b5cf6);
      color: white;
      border: none;
      padding: 0 18px;
      border-radius: 25px;
      cursor: pointer;
      font-size: 18px;
      min-width: 52px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .rba-chat-footer button:hover {
      transform: scale(1.05);
      box-shadow: 0 4px 15px rgba(91,53,213,0.35);
    }

    .rba-powered-by {
      text-align: center;
      padding: 6px;
      font-size: 10px;
      color: #9ca3af;
      background: #fafafa;
      border-top: 1px solid #f0f0f0;
      flex-shrink: 0;
    }
    .rba-powered-by a {
      color: #8b5cf6;
      text-decoration: none;
      font-weight: 600;
    }

    @media(max-width: 480px) {
      .rba-chat-widget {
        right: 8px;
        left: 8px;
        bottom: 92px;
        width: auto;
        height: 520px;
        max-height: calc(100vh - 110px);
        border-radius: 18px;
      }
      .rba-chat-toggle {
        right: 16px;
        bottom: 16px;
        width: 64px;
        height: 64px;
      }
    }
  `;

  // ====== INJECT STYLES ======
  const styleEl = document.createElement("style");
  styleEl.id = "rba-chatbot-styles";
  styleEl.textContent = WIDGET_STYLES;
  document.head.appendChild(styleEl);

  // ====== BUILD HTML ======
  const container = document.createElement("div");
  container.id = "rba-chatbot-container";
  container.innerHTML = `
    <button class="rba-chat-toggle" id="rbaChatToggle" aria-label="Open Chat">
      <img src="${PET_IMAGE}" alt="RoboAIAPaths Assistant">
    </button>

    <div class="rba-chat-widget" id="rbaChatWidget">
      <div class="rba-chat-header">
        <div class="rba-chat-header-left">
          <div class="rba-pet-avatar">
            <img src="${PET_IMAGE}" alt="Robo Pet">
          </div>
          <div class="rba-chat-title">
            <h3>RoboAIAPaths Assistant</h3>
            <p><span class="rba-online-dot"></span>Online • Robotics • AI • Coding</p>
          </div>
        </div>
        <button class="rba-chat-close" id="rbaChatClose" aria-label="Close Chat">×</button>
      </div>

      <div class="rba-chat-body" id="rbaChatBody">
        <div class="rba-message rba-bot-message">Hi 👋 Welcome to RoboAIAPaths!

I can help you with:
• Robotics & AI Classes
• Coding Courses
• Demo Class Booking
• Course Details & Pricing</div>

        <div class="rba-quick-buttons" id="rbaQuickButtons">
          <button onclick="window._rbaChatbot.quickAsk('I want to book a demo class')">🎯 Book Demo</button>
          <button onclick="window._rbaChatbot.quickAsk('Tell me about robotics classes')">🤖 Robotics</button>
          <button onclick="window._rbaChatbot.quickAsk('Tell me about AI and coding classes')">🧠 AI & Coding</button>
          <button onclick="window._rbaChatbot.quickAsk('What courses do you offer?')">📚 All Courses</button>
        </div>

        <div class="rba-typing" id="rbaTypingBox">
          <span></span><span></span><span></span>
        </div>
      </div>

      <div class="rba-demo-box">
        💡 For demo class, share: Parent Name, Phone Number, Child Class & Course Interest.
      </div>

      <div class="rba-chat-footer">
        <input
          type="text"
          id="rbaUserInput"
          placeholder="Type your message..."
          autocomplete="off"
        >
        <button id="rbaSendBtn" aria-label="Send Message">➤</button>
      </div>

      <div class="rba-powered-by">
        Powered by <a href="https://roboaiapaths.com" target="_blank">RoboAIAPaths AI</a>
      </div>
    </div>
  `;

  // ====== INJECT INTO DOM ======
  document.body.appendChild(container);

  // ====== CHATBOT LOGIC ======
  const chatWidget = document.getElementById("rbaChatWidget");
  const chatBody = document.getElementById("rbaChatBody");
  const userInput = document.getElementById("rbaUserInput");
  const typingBox = document.getElementById("rbaTypingBox");
  const toggleBtn = document.getElementById("rbaChatToggle");
  const closeBtn = document.getElementById("rbaChatClose");
  const sendBtn = document.getElementById("rbaSendBtn");

  let isOpen = false;

  function toggleChat() {
    if (isOpen) {
      chatWidget.style.display = "none";
      isOpen = false;
    } else {
      chatWidget.style.display = "flex";
      isOpen = true;
      scrollToBottom();
      userInput.focus();
    }
  }

  function addMessage(text, type) {
    const msg = document.createElement("div");
    msg.classList.add("rba-message");
    msg.classList.add(type === "user" ? "rba-user-message" : "rba-bot-message");
    msg.textContent = text;
    chatBody.insertBefore(msg, typingBox);
    scrollToBottom();
  }

  function showTyping() {
    typingBox.style.display = "block";
    scrollToBottom();
  }

  function hideTyping() {
    typingBox.style.display = "none";
  }

  function scrollToBottom() {
    chatBody.scrollTop = chatBody.scrollHeight;
  }

  function quickAsk(text) {
    userInput.value = text;
    sendMessage();
    // Hide quick buttons after first use
    const qb = document.getElementById("rbaQuickButtons");
    if (qb) qb.style.display = "none";
  }

  async function sendMessage() {
    const message = userInput.value.trim();
    if (!message) return;

    addMessage(message, "user");
    userInput.value = "";
    showTyping();

    // Hide quick buttons after user sends a message
    const qb = document.getElementById("rbaQuickButtons");
    if (qb) qb.style.display = "none";

    try {
      const response = await fetch(CHATBOT_API_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "chat",
          message: message,
          session_id: SESSION_ID,
        }),
      });

      const data = await response.json();
      hideTyping();
      addMessage(
        data.reply || "Sorry, I could not understand. Please try again.",
        "bot"
      );
    } catch (error) {
      hideTyping();
      addMessage(
        "Sorry, I'm having trouble connecting right now. Please try again or call us at +91 9990911093.",
        "bot"
      );
      console.error("RoboAIAPaths Chatbot Error:", error);
    }
  }

  // ====== EVENT LISTENERS ======
  toggleBtn.addEventListener("click", toggleChat);
  closeBtn.addEventListener("click", toggleChat);
  sendBtn.addEventListener("click", sendMessage);
  userInput.addEventListener("keydown", function (e) {
    if (e.key === "Enter") sendMessage();
  });

  // ====== EXPOSE GLOBAL API (for quick buttons) ======
  window._rbaChatbot = {
    quickAsk: quickAsk,
    toggle: toggleChat,
  };
})();
