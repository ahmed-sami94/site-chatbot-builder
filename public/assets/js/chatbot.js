(function (window, document) {
  "use strict";

  function t(lang, key) {
    var labels = {
      ar: {
        placeholder: "اكتب سؤالك هنا...",
        send: "إرسال",
        title: "المساعد المحلي",
        subtitle: "اسأل عن الموقع، ERP، التقارير، أو قاعدة البيانات",
        thinking: "جاري البحث...",
        error: "تعذر الاتصال بالمساعد.",
        source: "المصدر"
      },
      en: {
        placeholder: "Type your question...",
        send: "Send",
        title: "Local Assistant",
        subtitle: "Ask about website, ERP, reports, or database",
        thinking: "Searching...",
        error: "Could not reach the assistant.",
        source: "Source"
      }
    };
    return (labels[lang] && labels[lang][key]) || labels.en[key] || key;
  }

  function el(tag, className, text) {
    var node = document.createElement(tag);
    if (className) node.className = className;
    if (text) node.textContent = text;
    return node;
  }

  function appendMeta(parent, data, lang) {
    if (Array.isArray(data.cards) && data.cards.length) {
      var cards = el("div", "alc-cards");
      data.cards.forEach(function (card) {
        var item = el(card.url ? "a" : "div", "alc-card");
        if (card.url) {
          item.href = card.url;
          item.target = "_blank";
          item.rel = "noopener";
        }
        item.appendChild(el("strong", "", card.title || ""));
        if (card.subtitle) item.appendChild(el("span", "", card.subtitle));
        cards.appendChild(item);
      });
      parent.appendChild(cards);
    }

    if (Array.isArray(data.table_rows) && data.table_rows.length) {
      var table = el("table", "alc-table");
      data.table_rows.forEach(function (row) {
        var tr = document.createElement("tr");
        Object.keys(row).forEach(function (key) {
          var td = document.createElement("td");
          td.textContent = row[key];
          tr.appendChild(td);
        });
        table.appendChild(tr);
      });
      parent.appendChild(table);
    }

    if (Array.isArray(data.sources) && data.sources.length) {
      var sources = el("div", "alc-sources");
      data.sources.forEach(function (source) {
        sources.appendChild(el("span", "", t(lang, "source") + ": " + (source.label || source.ref || "")));
      });
      parent.appendChild(sources);
    }
  }

  function appendMessage(container, role, text, data, lang) {
    var item = el("div", "alc-message alc-" + role);
    item.appendChild(el("div", "alc-bubble", text || ""));
    if (data && role === "assistant") {
      appendMeta(item, data, lang);
    }
    container.appendChild(item);
    container.scrollTop = container.scrollHeight;
    return item;
  }

  function mount(selector, options) {
    var root = typeof selector === "string" ? document.querySelector(selector) : selector;
    if (!root) return;

    options = options || {};
    var lang = (options.language || document.documentElement.lang || "en").toLowerCase() === "ar" ? "ar" : "en";
    var sessionId = null;

    root.className += " adaptive-local-chatbot";
    root.setAttribute("dir", lang === "ar" ? "rtl" : "ltr");
    root.innerHTML = "";

    var head = el("div", "alc-head");
    head.appendChild(el("strong", "", t(lang, "title")));
    head.appendChild(el("span", "", t(lang, "subtitle")));
    var messages = el("div", "alc-messages");
    var form = el("form", "alc-form");
    var input = document.createElement("input");
    input.type = "text";
    input.placeholder = t(lang, "placeholder");
    input.autocomplete = "off";
    var button = el("button", "", t(lang, "send"));
    button.type = "submit";
    form.appendChild(input);
    form.appendChild(button);

    root.appendChild(head);
    root.appendChild(messages);
    root.appendChild(form);

    appendMessage(messages, "assistant", lang === "ar" ? "أهلا بك. كيف أستطيع مساعدتك؟" : "Hello. How can I help?", null, lang);

    form.addEventListener("submit", function (event) {
      event.preventDefault();
      var message = input.value.trim();
      if (!message) return;
      input.value = "";
      appendMessage(messages, "user", message, null, lang);
      var thinking = appendMessage(messages, "assistant", t(lang, "thinking"), null, lang);

      var payload = {
        message: message,
        session_id: sessionId,
        context_type: options.context_type || "",
        context_id: options.context_id || "",
        context_title: options.context_title || document.title || "",
        language: lang
      };

      fetch(options.endpoint || "/chatbot/public/api/chat.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          if (thinking.parentNode) thinking.parentNode.removeChild(thinking);
          if (data.session_id) sessionId = data.session_id;
          appendMessage(messages, "assistant", data.answer || t(lang, "error"), data, lang);
        })
        .catch(function () {
          if (thinking.parentNode) thinking.parentNode.removeChild(thinking);
          appendMessage(messages, "assistant", t(lang, "error"), { handoff: true }, lang);
        });
    });
  }

  window.AdaptiveLocalChatbot = { mount: mount };
})(window, document);
