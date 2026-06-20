<?php
// Drop this snippet into an existing PHP page after copying the chatbot folder.
?>
<link rel="stylesheet" href="/chatbot/public/assets/css/chatbot.css">
<div id="adaptiveLocalChatbot"></div>
<script src="/chatbot/public/assets/js/chatbot.js"></script>
<script>
  AdaptiveLocalChatbot.mount("#adaptiveLocalChatbot", {
    endpoint: "/chatbot/public/api/chat.php",
    language: document.documentElement.lang || "ar",
    context_type: "website",
    context_id: "<?php echo isset($page_id) ? htmlspecialchars((string)$page_id, ENT_QUOTES, 'UTF-8') : ''; ?>",
    context_title: document.title
  });
</script>
