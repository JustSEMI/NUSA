//
/**
 * NUSA ChatBot v2.0 - Refactored & Modular
 * Clean architecture without ES6 modules (Vite compatible)
 */

const CONFIG = {
    CONTEXT_WINDOW: 10,
    COPY_FEEDBACK_DURATION: 2000,
    ANIMATION_DURATION: {
        FAST: 50,
        NORMAL: 200,
    },
    STORAGE_KEYS: {
        DARK_MODE: "nusa-dark-mode",
        SETTINGS: "nusa-settings",
        DEFAULT_MODEL: "nusa-default-model",
        THINKING_EFFORT: "nusa-thinking-effort",
    },
    SELECTORS: {
        chatMessages: "chatMessages",
        messages: "messages",
        welcomeState: "welcomeState",
        chatForm: "chatForm",
        messageInput: "messageInput",
        sendButton: "sendButton",
        stopButton: "stopButton",
        chatSidebar: "chatSidebar",
        sidebarBackdrop: "sidebarBackdrop",
        newChatBtn: "newChatBtn",
        chatHistoryList: "chatHistoryList",
        modelDropdownBtn: "modelDropdownBtn",
        modelDropdownMenu: "modelDropdownMenu",
        modelDropdownIcon: "modelDropdownIcon",
        selectedModelText: "selectedModelText",
        thinkingEffortBtn: "thinkingEffortBtn",
        thinkingEffortMenu: "thinkingEffortMenu",
        thinkingEffortIcon: "thinkingEffortIcon",
        thinkingEffortText: "thinkingEffortText",
        attachmentPreviewTray: "attachmentPreviewTray",
        attachFileBtn: "attachFileBtn",
        attachFileIcon: "attachFileIcon",
        attachMenu: "attachMenu",
        menuUploadFile: "menuUploadFile",
        menuUploadPhoto: "menuUploadPhoto",
        menuUploadCode: "menuUploadCode",
        fileInput: "fileInput",
        fileInputDocument: "fileInputDocument",
        fileInputImage: "fileInputImage",
        fileInputCode: "fileInputCode",
        dragDropOverlay: "dragDropOverlay",
    },
};

const Utils = {
    getStorage(key, fallback = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : fallback;
        } catch {
            // Silent fail for localStorage read errors
            return fallback;
        }
    },

    setStorage(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            // Silent fail for localStorage errors
        }
    },

    formatTime(dateString) {
        return new Date(dateString).toLocaleTimeString("id-ID", {
            hour: "2-digit",
            minute: "2-digit",
        });
    },

    formatDate(dateString) {
        const date = new Date(dateString);
        const day = date.getDate();
        const month = date.toLocaleString("id-ID", { month: "short" });
        const year = date.getFullYear();
        return `${day} ${month} ${year}`;
    },

    escapeHtml(text) {
        if (!text) return "";
        const div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
    },

    decodeHtml(html) {
        const txt = document.createElement("textarea");
        txt.innerHTML = html;
        return txt.value;
    },
};

class ChatState {
    constructor() {
        this.sessionId = null;
        this.messages = [];
        this.stagedAttachments = [];
        this.isTyping = false;
        this.typingAnimation = null;
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        this.darkMode = Utils.getStorage(CONFIG.STORAGE_KEYS.DARK_MODE, prefersDark);
        this.settings = Utils.getStorage(CONFIG.STORAGE_KEYS.SETTINGS, {
            auto_save: true,
            streaming: true,
        });
        this.selectedModel =
            localStorage.getItem(CONFIG.STORAGE_KEYS.DEFAULT_MODEL) ||
            "qwen-3.5-flash";
        this.thinkingEffort = Utils.getStorage(
            CONFIG.STORAGE_KEYS.THINKING_EFFORT,
            "high",
        );
    }

    toggleDarkMode() {
        this.darkMode = !this.darkMode;
        Utils.setStorage(CONFIG.STORAGE_KEYS.DARK_MODE, this.darkMode);
        return this.darkMode;
    }

    updateSetting(key, value) {
        this.settings[key] = value;
        Utils.setStorage(CONFIG.STORAGE_KEYS.SETTINGS, this.settings);
    }

    addMessage(role, content, attachments = null) {
        this.messages.push({ role, content, attachments });
    }

    getLastUserMessageIndex() {
        for (let i = this.messages.length - 1; i >= 0; i--) {
            if (this.messages[i].role === "user") {
                return i;
            }
        }
        return -1;
    }

    clearMessages() {
        this.messages = [];
    }

    clearStagedAttachments() {
        this.stagedAttachments = [];
    }

    setSessionId(sessionId) {
        this.sessionId = sessionId;
    }
}

class UIManager {
    constructor() {
        this.elements = {};
        this.cacheElements();
    }

    cacheElements() {
        Object.entries(CONFIG.SELECTORS).forEach(([key, id]) => {
            this.elements[key] = document.getElementById(id);
        });
    }

    get(key) {
        return this.elements[key];
    }

    toggleSidebar(open) {
        const sidebar = this.get("chatSidebar");
        const backdrop = this.get("sidebarBackdrop");

        if (!sidebar || !backdrop) return;

        if (open) {
            sidebar.classList.remove("-translate-x-full");
            backdrop.classList.remove("hidden");
        } else {
            sidebar.classList.add("-translate-x-full");
            backdrop.classList.add("hidden");
        }
    }

    setLoading(loading) {
        const sendBtn = this.get("sendButton");
        const stopBtn = this.get("stopButton");

        if (!sendBtn || !stopBtn) return;

        const { fromBtn, toBtn } = loading
            ? { fromBtn: sendBtn, toBtn: stopBtn }
            : { fromBtn: stopBtn, toBtn: sendBtn };

        // Hide current button
        fromBtn.style.opacity = "0";
        fromBtn.style.transform = "scale(0.8)";

        setTimeout(() => {
            fromBtn.classList.add("hidden");
            fromBtn.style.display = "none";

            // Show target button
            toBtn.classList.remove("hidden");
            toBtn.style.display = "inline-flex";
            toBtn.style.opacity = "0";
            toBtn.style.transform = "scale(0.8)";
            toBtn.disabled = false;

            setTimeout(() => {
                toBtn.style.opacity = "1";
                toBtn.style.transform = "scale(1)";
            }, CONFIG.ANIMATION_DURATION.FAST);
        }, CONFIG.ANIMATION_DURATION.NORMAL);
    }

    clearChat() {
        const messages = this.get("messages");
        const welcome = this.get("welcomeState");

        if (messages) {
            messages.innerHTML = "";
            messages.classList.add("hidden");
        }

        if (welcome) {
            welcome.classList.remove("hidden");
        }

        const deleteBtn = this.get("deleteChatBtn");
        if (deleteBtn) deleteBtn.disabled = true;
    }

    showTypingIndicator() {
        const messages = this.get("messages");
        if (!messages) return null;

        const row = document.createElement("div");
        row.className = "flex justify-start";
        row.innerHTML = `
            <div class="w-auto max-w-[95%] sm:max-w-3xl lg:max-w-4xl xl:max-w-5xl rounded-2xl sm:rounded-lg bg-white dark:!bg-[#202222] border border-gray-200 dark:border-[#2F3030] px-4 py-3.5">
                <div class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        `;

        messages.appendChild(row);
        const chatMessages = this.get("chatMessages");
        if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;
        return row;
    }

    renderAttachmentTray(attachments, onRemove) {
        const tray = this.get("attachmentPreviewTray");
        if (!tray) return;

        if (!attachments || attachments.length === 0) {
            tray.innerHTML = "";
            tray.classList.add("hidden");
            return;
        }

        tray.classList.remove("hidden");
        tray.innerHTML = "";

        attachments.forEach((att, index) => {
            const chip = document.createElement("div");
            chip.className = "attachment-preview-chip";

            let iconOrImg = "";
            if (att.is_image && att.url) {
                iconOrImg = `<img src="${att.url}" alt="${Utils.escapeHtml(att.name)}" class="rounded">`;
            } else if (att.isUploading) {
                iconOrImg = `<div class="inline-block animate-spin rounded-full h-4 w-4 border-2 border-gray-400 border-t-emerald-500"></div>`;
            } else {
                iconOrImg = `<i data-lucide="file-text" class="h-4 w-4 text-emerald-500 shrink-0"></i>`;
            }

            chip.innerHTML = `
                ${iconOrImg}
                <div class="truncate flex-1">
                    <div class="font-medium truncate text-xs text-gray-800 dark:text-gray-200">${Utils.escapeHtml(att.name)}</div>
                    <div class="text-[10px] text-gray-400">${att.size_formatted || ""}</div>
                </div>
                <button type="button" class="attachment-chip-remove" title="Hapus">
                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                </button>
            `;

            const removeBtn = chip.querySelector(".attachment-chip-remove");
            if (removeBtn) {
                removeBtn.addEventListener("click", (e) => {
                    e.stopPropagation();
                    if (typeof onRemove === "function") {
                        onRemove(index);
                    }
                });
            }

            tray.appendChild(chip);
        });

        if (typeof lucide !== "undefined") {
            lucide.createIcons();
        }
    }

    showDragOverlay(show) {
        const overlay = this.get("dragDropOverlay");
        if (!overlay) return;
        if (show) {
            overlay.classList.remove("hidden");
            overlay.classList.add("flex");
        } else {
            overlay.classList.add("hidden");
            overlay.classList.remove("flex");
        }
    }

    addUserMessage(text, attachments = null) {
        const messages = this.get("messages");
        if (!messages) return;

        const row = document.createElement("div");
        row.className = "flex justify-end message-animate";

        let attachmentsHtml = "";
        if (attachments && attachments.length > 0) {
            const images = attachments.filter((a) => a.is_image && a.url);
            const docs = attachments.filter((a) => !a.is_image || !a.url);

            let imagesHtml = "";
            if (images.length > 0) {
                imagesHtml = `
                    <div class="msg-attachment-grid">
                        ${images
                            .map(
                                (img) => `
                            <img src="${img.url}" alt="${Utils.escapeHtml(img.name)}" class="msg-attachment-img" onclick="openLightbox('${img.url}')">
                        `,
                            )
                            .join("")}
                    </div>
                `;
            }

            let docsHtml = "";
            if (docs.length > 0) {
                docsHtml = `
                    <div class="flex flex-col gap-1.5 mb-2">
                        ${docs
                            .map(
                                (doc) => `
                            <a href="${doc.url || "#"}" target="_blank" class="msg-attachment-doc" download="${Utils.escapeHtml(doc.name)}">
                                <i data-lucide="file-text" class="h-4 w-4 text-emerald-400 shrink-0"></i>
                                <span class="truncate max-w-[200px] font-medium">${Utils.escapeHtml(doc.name)}</span>
                                <span class="text-[10px] text-gray-300 opacity-75">${doc.size_formatted || ""}</span>
                                <i data-lucide="download" class="h-3 w-3 ml-auto opacity-70"></i>
                            </a>
                        `,
                            )
                            .join("")}
                    </div>
                `;
            }

            attachmentsHtml = imagesHtml + docsHtml;
        }

        const textHtml = text
            ? `<p class="text-sm sm:text-base whitespace-pre-wrap break-words">${Utils.escapeHtml(text)}</p>`
            : "";

        row.innerHTML = `
            <div class="w-auto max-w-[85%] sm:max-w-xl md:max-w-2xl rounded-2xl sm:rounded-lg bg-gray-900 dark:!bg-[#202222] text-white px-4 py-3">
                ${attachmentsHtml}
                ${textHtml}
                <div class="flex items-center justify-end gap-2 mt-2">
                    <span class="text-xs text-gray-400 dark:text-gray-500">${Utils.formatTime(new Date())}</span>
                </div>
            </div>
        `;

        messages.appendChild(row);
        if (typeof lucide !== "undefined") {
            lucide.createIcons();
        }

        const chatMessages = this.get("chatMessages");
        if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;

        const deleteBtn = this.get("deleteChatBtn");
        if (deleteBtn) deleteBtn.disabled = false;
    }

    addAssistantMessage(text, index) {
        const messages = this.get("messages");
        if (!messages) return null;

        const row = document.createElement("div");
        row.className = "flex justify-start message-animate";
        row.dataset.messageIndex = index;
        row.innerHTML = `
            <div class="w-auto max-w-[95%] sm:max-w-3xl lg:max-w-4xl xl:max-w-5xl rounded-2xl sm:rounded-lg bg-white dark:!bg-[#202222] border border-gray-200 dark:border-[#2F3030] px-4 py-3.5">
                <div class="markdown-content text-sm sm:text-base text-gray-900 dark:text-gray-100 leading-relaxed overflow-x-auto"></div>
                <div class="flex items-center gap-3 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <button class="action-btn" data-action="edit" title="Edit">
                        <i data-lucide="edit-2" class="h-3.5 w-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"></i>
                    </button>
                    <button class="action-btn" data-action="regenerate" title="Regenerate">
                        <i data-lucide="refresh-cw" class="h-3.5 w-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"></i>
                    </button>
                    <button class="action-btn" data-action="copy" title="Copy">
                        <i data-lucide="copy" class="h-3.5 w-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"></i>
                    </button>
                    <div class="flex-1"></div>
                    <span class="text-xs text-gray-400 dark:text-gray-500">${Utils.formatTime(new Date())}</span>
                </div>
            </div>
        `;

        messages.appendChild(row);

        // Setup action button handlers using event delegation
        row.querySelectorAll("[data-action]").forEach((btn) => {
            btn.addEventListener("click", () => {
                const action = btn.dataset.action;
                const msgIndex = parseInt(row.dataset.messageIndex, 10);

                if (action === "edit" && window.chatBot) {
                    window.chatBot.editMessage(msgIndex);
                } else if (action === "regenerate" && window.chatBot) {
                    window.chatBot.regenerateMessage(msgIndex);
                } else if (action === "copy" && window.chatBot) {
                    window.chatBot.copyMessage(msgIndex);
                }
            });
        });

        if (typeof lucide !== "undefined") {
            lucide.createIcons();
        }

        const deleteBtn = this.get("deleteChatBtn");
        if (deleteBtn) deleteBtn.disabled = false;

        return row.querySelector(".markdown-content");
    }

    showError(data) {
        const messages = this.get("messages");
        if (!messages) return;

        const errorCode = data?.code;
        let userFriendlyMessage = "Terjadi kesalahan pada server AI";

        // Map error codes to user-friendly messages
        if (errorCode === "NETWORK_ERROR" || errorCode === 0) {
            userFriendlyMessage =
                "Tidak dapat terhubung ke server. Periksa koneksi internet Anda dan coba lagi.";
        } else if (
            errorCode === 408 ||
            (data?.message && data.message.includes("timed out"))
        ) {
            userFriendlyMessage =
                "Request timeout. Server AI sedang sibuk, silakan coba beberapa saat lagi.";
        } else if (errorCode === 429) {
            userFriendlyMessage =
                "Terlalu banyak request. Silakan tunggu beberapa saat sebelum mengirim pesan lagi.";
        } else if (
            errorCode === 500 ||
            errorCode === 502 ||
            errorCode === 503
        ) {
            userFriendlyMessage =
                "Server AI sedang mengalami gangguan. Silakan coba beberapa saat lagi.";
        } else if (
            data?.message &&
            data.message.includes("Provider rejected")
        ) {
            userFriendlyMessage =
                "AI provider menolak request. Kemungkinan ada masalah dengan API key atau quota.";
        } else if (data?.message) {
            userFriendlyMessage = data.message;
        } else if (data?.error) {
            userFriendlyMessage = data.error;
        }

        const row = document.createElement("div");
        row.className = "flex justify-start message-animate";
        row.innerHTML = `
            <div class="max-w-xl sm:max-w-2xl rounded-2xl sm:rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3.5">
                <div class="markdown-content text-sm sm:text-base text-red-800 dark:text-red-300 leading-relaxed">
                    **Connection Error**

${userFriendlyMessage}

Jika masalah berlanjut, hubungi developer.
                </div>
            </div>
        `;

        messages.appendChild(row);
        const chatMessages = this.get("chatMessages");
        if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    updateModelDisplay(model, modelData) {
        const selectedModelText = this.get("selectedModelText");
        if (!selectedModelText) return;

        const data = modelData[model] ||
            modelData["qwen-3.5-flash"] || {
                name: "Qwen 3.5 Flash",
                multiplier: "1x",
            };
        selectedModelText.textContent = data.name;
    }

    setDarkMode(isDark) {
        if (isDark) {
            document.documentElement.classList.add("dark");
        } else {
            document.documentElement.classList.remove("dark");
        }
    }
}

class ApiService {
    constructor(csrfToken) {
        this.csrfToken = csrfToken;
        this.baseHeaders = {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken,
            Accept: "application/json",
        };
    }

    async uploadFiles(files) {
        try {
            const formData = new FormData();
            for (let i = 0; i < files.length; i++) {
                formData.append("files[]", files[i]);
            }

            const response = await fetch("/api/chat/upload", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": this.csrfToken,
                    Accept: "application/json",
                },
                body: formData,
            });

            return await response.json();
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    async uploadBase64(base64Data, customName = null) {
        try {
            const response = await fetch("/api/chat/upload", {
                method: "POST",
                headers: this.baseHeaders,
                body: JSON.stringify({
                    base64_data: base64Data,
                    custom_name: customName,
                }),
            });

            return await response.json();
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    async sendMessage(message, model, sessionId = null, attachments = []) {
        try {
            const payload = {
                message: message || "",
                model,
                session_id: sessionId,
            };
            if (attachments && attachments.length > 0) {
                payload.attachments = attachments;
            }

            const response = await fetch("/api/chat", {
                method: "POST",
                headers: this.baseHeaders,
                body: JSON.stringify(payload),
            });

            // Check if response is JSON
            const contentType = response.headers.get("content-type");
            let data;

            if (contentType && contentType.includes("application/json")) {
                data = await response.json();
            } else {
                // Non-JSON response
                const text = await response.text();
                data = {
                    message:
                        "Server error. Silakan refresh halaman dan coba lagi.",
                };
            }

            if (!response.ok) {
                // Handle auth/session issues
                if (response.status === 401 || response.status === 419) {
                    return {
                        ok: false,
                        error: {
                            message:
                                "Sesi Anda telah berakhir. Silakan login ulang.",
                            code: "AUTH_ERROR",
                        },
                    };
                }

                return {
                    ok: false,
                    error: {
                        message:
                            data.message ||
                            data.error ||
                            "Terjadi kesalahan pada server",
                        code: response.status,
                    },
                };
            }

            return { ok: true, data };
        } catch (error) {
            // Network error or timeout
            return {
                ok: false,
                error: {
                    message:
                        "Tidak dapat terhubung ke server. Periksa koneksi internet Anda.",
                    code: "NETWORK_ERROR",
                },
            };
        }
    }

    async getHistory() {
        try {
            const response = await fetch("/api/chat/history", {
                headers: this.baseHeaders,
            });
            return await response.json();
        } catch {
            return { sessions: [] };
        }
    }

    async getChatSession(id) {
        try {
            const response = await fetch(`/api/chat/${id}`, {
                headers: { Accept: "application/json" },
            });
            return await response.json();
        } catch {
            return null;
        }
    }

    async deleteSession(id) {
        try {
            const response = await fetch(`/api/chat/session/${id}`, {
                method: "DELETE",
                headers: { "X-CSRF-TOKEN": this.csrfToken },
            });
            return await response.json();
        } catch {
            return { success: false };
        }
    }
    
    async pinSession(id) {
        try {
            const response = await fetch(`/api/chat/session/${id}/pin`, {
                method: "PUT",
                headers: { "X-CSRF-TOKEN": this.csrfToken },
            });
            return await response.json();
        } catch {
            return { success: false };
        }
    }

    async renameSession(id, title) {
        try {
            const response = await fetch(`/api/chat/session/${id}/rename`, {
                method: "PUT",
                headers: { 
                    "X-CSRF-TOKEN": this.csrfToken,
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ title })
            });
            return await response.json();
        } catch {
            return { success: false };
        }
    }
    async truncateMessages(id, index) {
        try {
            const response = await fetch(`/api/chat/session/${id}/truncate/${index}`, {
                method: "DELETE",
                headers: { "X-CSRF-TOKEN": this.csrfToken },
            });
            return await response.json();
        } catch {
            return { success: false };
        }
    }
}

class MarkdownRenderer {
    constructor() {
        this.md = window.md;
    }

    render(element, text, isStreaming = false) {
        if (!element || !text) return;

        if (!this.md || typeof this.md.render !== "function") {
            element.textContent = text;
            return;
        }

        try {
            let html = this.md.render(text);
            html = this.wrapCodeBlocks(html);
            element.innerHTML = html;
            if (!isStreaming) {
                this.initializeIcons();
                this.highlightCode(element);
            }
        } catch {
            // Silent fail for markdown rendering
            element.textContent = text;
        }
    }

    wrapCodeBlocks(html) {
        const codeBlockRegex =
            /<pre><code(?: class="language-(\w+)")?>([\s\S]*?)<\/code><\/pre>/g;

        return html.replace(codeBlockRegex, (match, lang, codeContent) => {
            const language = lang || "plaintext";
            const cleanCode = Utils.decodeHtml(codeContent);
            const escapedCode = encodeURIComponent(cleanCode);

            return `
<div class="code-block-wrapper">
    <div class="code-block-header">
        <span class="code-block-language">${language}</span>
        <button class="copy-code-btn" onclick="window.chatBot.copyCode(this)" data-code="${escapedCode}">
            <i data-lucide="copy" class="h-3.5 w-3.5"></i>
            <span>Copy</span>
        </button>
    </div>
    <pre><code class="language-${language}">${Utils.escapeHtml(cleanCode)}</code></pre>
</div>`;
        });
    }

    initializeIcons() {
        if (typeof lucide !== "undefined") {
            lucide.createIcons();
        }
    }

    highlightCode(element) {
        if (typeof Prism === "undefined") return;
        element.querySelectorAll("pre code").forEach((block) => {
            try {
                Prism.highlightElement(block);
            } catch (err) {
                /* Silently ignore */
            }
        });
    }

    copyCode(button) {
        const encodedCode = button.getAttribute("data-code");
        const code = decodeURIComponent(encodedCode);

        navigator.clipboard
            .writeText(code)
            .then(() => {
                const originalHTML = button.innerHTML;
                button.innerHTML =
                    '<i data-lucide="check" class="h-3.5 w-3.5"></i><span>Copied!</span>';
                button.style.cssText =
                    "background-color: #22c55e; border-color: #22c55e; color: #ffffff;";

                if (typeof lucide !== "undefined") lucide.createIcons();

                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.style.cssText = "";
                    if (typeof lucide !== "undefined") lucide.createIcons();
                }, CONFIG.COPY_FEEDBACK_DURATION);
            })
            .catch(() => {
                // Silent fail for copy errors
            });
    }
}

class ChatBot {
    constructor() {
        this.state = new ChatState();
        this.ui = new UIManager();
        this.api = new ApiService(this.getCsrfToken());
        this.markdown = new MarkdownRenderer();
        this.modelData = window.chatConfig?.availableModels || {};
        this.dragCounter = 0;

        // Validate and fix model if needed
        this.validateAndFixModel();

        this.init();
    }

    validateAndFixModel() {
        const validModels = Object.keys(this.modelData);
        if (validModels.length === 0) return; // Prevent overwriting when config isn't loaded (e.g. on settings page)

        const currentModel = localStorage.getItem(
            CONFIG.STORAGE_KEYS.DEFAULT_MODEL,
        );

        // If no model in localStorage or invalid model, set to first valid model
        if (!currentModel || !validModels.includes(currentModel)) {
            const defaultModel = validModels[0] || "qwen-3.5-flash";
            localStorage.setItem(
                CONFIG.STORAGE_KEYS.DEFAULT_MODEL,
                defaultModel,
            );
            this.state.selectedModel = defaultModel;
        }
    }

    getCsrfToken() {
        return (
            window.chatConfig?.csrfToken ||
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ||
            ""
        );
    }

    async init() {
        this.setupEventListeners();
        this.setupAttachmentHandlers();
        this.setupModelDropdown();
        this.setupThinkingEffortDropdown();
        await this.loadChatHistory();
        
        // Setup Search History Listener
        const searchInput = document.getElementById("searchHistoryInput");
        if (searchInput) {
            searchInput.addEventListener("input", (e) => {
                const term = e.target.value.toLowerCase();
                if (!this.state.sessions) return;
                
                const filtered = this.state.sessions.filter(s => 
                    s.title.toLowerCase().includes(term)
                );
                this.renderFilteredHistory(filtered);
            });
        }

        this.applyDarkMode();
        window.chatBot = this;
    }

    setupEventListeners() {
        const form = this.ui.get("chatForm");
        const input = this.ui.get("messageInput");
        const newChatBtn = this.ui.get("newChatBtn");
        const stopBtn = this.ui.get("stopButton");

        if (form) {
            form.addEventListener("submit", (e) => this.handleSubmit(e));
        }

        if (input) {
            input.addEventListener("keydown", (e) => {
                if (e.key === "Enter" && !e.shiftKey) {
                    e.preventDefault();
                    form?.dispatchEvent(
                        new Event("submit", { cancelable: true }),
                    );
                }
            });
        }

        if (newChatBtn) {
            newChatBtn.addEventListener("click", () => this.newChat());
        }

        if (stopBtn) {
            stopBtn.addEventListener("click", () => this.stopTyping());
        }

        const darkModeToggle = this.ui.get("darkModeToggle");
        if (darkModeToggle) {
            darkModeToggle.addEventListener("click", () =>
                this.toggleDarkMode(),
            );
        }
    }

    setupModelDropdown() {
        const dropdownBtn = this.ui.get("modelDropdownBtn");
        const dropdownMenu = this.ui.get("modelDropdownMenu");
        const dropdownIcon = this.ui.get("modelDropdownIcon");

        if (!dropdownBtn || !dropdownMenu) return;

        dropdownBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            const isOpen = !dropdownMenu.classList.contains("invisible");

            if (isOpen) {
                // Close dropdown
                dropdownMenu.classList.add(
                    "invisible",
                    "opacity-0",
                    "translate-y-2",
                );
                dropdownMenu.classList.remove(
                    "visible",
                    "opacity-100",
                    "translate-y-0",
                );
                dropdownIcon.style.transform = "rotate(0deg)";
            } else {
                // Open dropdown
                // Close effort menu if open
                const effortMenu = this.ui.get("thinkingEffortMenu");
                const effortIcon = this.ui.get("thinkingEffortIcon");
                if (effortMenu && !effortMenu.classList.contains("invisible")) {
                    effortMenu.classList.add("invisible", "opacity-0", "translate-y-2");
                    effortMenu.classList.remove("visible", "opacity-100", "translate-y-0");
                    if (effortIcon) effortIcon.style.transform = "rotate(0deg)";
                }

                // Close attach menu if open
                const attachMenu = this.ui.get("attachMenu");
                const attachIcon = this.ui.get("attachFileIcon");
                const attachBtn = this.ui.get("attachFileBtn");
                if (attachMenu && !attachMenu.classList.contains("invisible")) {
                    attachMenu.classList.add("invisible", "opacity-0", "translate-y-2", "pointer-events-none");
                    attachMenu.classList.remove("visible", "opacity-100", "translate-y-0", "pointer-events-auto");
                    if (attachBtn) attachBtn.setAttribute("aria-expanded", "false");
                    if (attachIcon) attachIcon.style.transform = "rotate(0deg)";
                }

                dropdownMenu.classList.remove(
                    "invisible",
                    "opacity-0",
                    "translate-y-2",
                );
                dropdownMenu.classList.add(
                    "visible",
                    "opacity-100",
                    "translate-y-0",
                );
                dropdownIcon.style.transform = "rotate(180deg)";
            }
        });

        document.addEventListener("click", (e) => {
            const isClickInsideDropdown =
                dropdownBtn.contains(e.target) ||
                dropdownMenu.contains(e.target);
            if (!isClickInsideDropdown) {
                dropdownMenu.classList.add(
                    "invisible",
                    "opacity-0",
                    "translate-y-2",
                );
                dropdownMenu.classList.remove(
                    "visible",
                    "opacity-100",
                    "translate-y-0",
                );
                dropdownIcon.style.transform = "rotate(0deg)";
            }
        });

        dropdownMenu.querySelectorAll(".model-option").forEach((option) => {
            option.addEventListener("click", (e) => {
                e.stopPropagation();
                const model = option.dataset.value;
                this.state.selectedModel = model;
                Utils.setStorage(CONFIG.STORAGE_KEYS.DEFAULT_MODEL, model);
                if (this.state.sessionId) {
                    Utils.setStorage(`nusa-model-sess-${this.state.sessionId}`, model);
                }
                this.ui.updateModelDisplay(model, this.modelData);
                dropdownMenu.classList.add(
                    "invisible",
                    "opacity-0",
                    "translate-y-2",
                );
                dropdownMenu.classList.remove(
                    "visible",
                    "opacity-100",
                    "translate-y-0",
                );
                dropdownIcon.style.transform = "rotate(0deg)";
            });
        });

        this.ui.updateModelDisplay(this.state.selectedModel, this.modelData);
    }

    setupThinkingEffortDropdown() {
        const dropdownBtn = this.ui.get("thinkingEffortBtn");
        const dropdownMenu = this.ui.get("thinkingEffortMenu");
        const dropdownIcon = this.ui.get("thinkingEffortIcon");
        const effortText = this.ui.get("thinkingEffortText");

        if (!dropdownBtn || !dropdownMenu) {
            return;
        }

        // Set initial value
        this.updateThinkingEffortDisplay(this.state.thinkingEffort);

        dropdownBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            const isOpen = !dropdownMenu.classList.contains("invisible");

            if (isOpen) {
                // Close dropdown
                dropdownMenu.classList.add(
                    "invisible",
                    "opacity-0",
                    "translate-y-2",
                );
                dropdownMenu.classList.remove(
                    "visible",
                    "opacity-100",
                    "translate-y-0",
                );
                dropdownIcon.style.transform = "rotate(0deg)";
            } else {
                // Open dropdown
                // Close model menu if open
                const modelMenu = this.ui.get("modelDropdownMenu");
                const modelIcon = this.ui.get("modelDropdownIcon");
                if (modelMenu && !modelMenu.classList.contains("invisible")) {
                    modelMenu.classList.add("invisible", "opacity-0", "translate-y-2");
                    modelMenu.classList.remove("visible", "opacity-100", "translate-y-0");
                    if (modelIcon) modelIcon.style.transform = "rotate(0deg)";
                }

                // Close attach menu if open
                const attachMenu = this.ui.get("attachMenu");
                const attachIcon = this.ui.get("attachFileIcon");
                const attachBtn = this.ui.get("attachFileBtn");
                if (attachMenu && !attachMenu.classList.contains("invisible")) {
                    attachMenu.classList.add("invisible", "opacity-0", "translate-y-2", "pointer-events-none");
                    attachMenu.classList.remove("visible", "opacity-100", "translate-y-0", "pointer-events-auto");
                    if (attachBtn) attachBtn.setAttribute("aria-expanded", "false");
                    if (attachIcon) attachIcon.style.transform = "rotate(0deg)";
                }

                dropdownMenu.classList.remove(
                    "invisible",
                    "opacity-0",
                    "translate-y-2",
                );
                dropdownMenu.classList.add(
                    "visible",
                    "opacity-100",
                    "translate-y-0",
                );
                dropdownIcon.style.transform = "rotate(180deg)";

                // Reinitialize lucide icons for the dropdown content
                setTimeout(() => {
                    if (typeof lucide !== "undefined") {
                        lucide.createIcons();
                    }
                }, 10);
            }
        });

        document.addEventListener("click", (e) => {
            const isClickInsideDropdown =
                dropdownBtn.contains(e.target) ||
                dropdownMenu.contains(e.target);
            if (!isClickInsideDropdown) {
                dropdownMenu.classList.add(
                    "invisible",
                    "opacity-0",
                    "translate-y-2",
                );
                dropdownMenu.classList.remove(
                    "visible",
                    "opacity-100",
                    "translate-y-0",
                );
                dropdownIcon.style.transform = "rotate(0deg)";
            }
        });

        dropdownMenu.querySelectorAll(".thinking-option").forEach((option) => {
            option.addEventListener("click", (e) => {
                e.stopPropagation();
                const effort = option.dataset.value;
                this.state.thinkingEffort = effort;
                Utils.setStorage(CONFIG.STORAGE_KEYS.THINKING_EFFORT, effort);
                if (this.state.sessionId) {
                    Utils.setStorage(`nusa-effort-sess-${this.state.sessionId}`, effort);
                }
                this.updateThinkingEffortDisplay(effort);
                dropdownMenu.classList.add(
                    "invisible",
                    "opacity-0",
                    "translate-y-2",
                );
                dropdownMenu.classList.remove(
                    "visible",
                    "opacity-100",
                    "translate-y-0",
                );
                dropdownIcon.style.transform = "rotate(0deg)";
            });
        });
    }

    updateThinkingEffortDisplay(effort) {
        const effortText = this.ui.get("thinkingEffortText");
        if (!effortText) return;

        const labels = {
            low: "Low",
            medium: "Medium",
            high: "High",
        };
        effortText.textContent = labels[effort] || "High";
    }

    setupAttachmentHandlers() {
        const attachBtn = this.ui.get("attachFileBtn");
        const attachIcon = this.ui.get("attachFileIcon") || (attachBtn ? attachBtn.querySelector("i, svg") : null);
        const attachMenu = this.ui.get("attachMenu");

        const fileInput = this.ui.get("fileInput");
        const fileInputDoc = this.ui.get("fileInputDocument") || fileInput;
        const fileInputImg = this.ui.get("fileInputImage") || fileInput;
        const fileInputCode = this.ui.get("fileInputCode") || fileInput;

        const menuUploadFile = this.ui.get("menuUploadFile");
        const menuUploadPhoto = this.ui.get("menuUploadPhoto");
        const menuUploadCode = this.ui.get("menuUploadCode");

        const closeAttachMenu = () => {
            if (!attachMenu) return;
            attachMenu.classList.add("invisible", "opacity-0", "translate-y-2", "pointer-events-none");
            attachMenu.classList.remove("visible", "opacity-100", "translate-y-0", "pointer-events-auto");
            if (attachBtn) {
                attachBtn.setAttribute("aria-expanded", "false");
            }
            if (attachIcon) {
                attachIcon.style.transform = "rotate(0deg)";
            }
        };

        const openAttachMenu = () => {
            if (!attachMenu) return;
            // Close other dropdowns if open
            const modelMenu = this.ui.get("modelDropdownMenu");
            const modelIcon = this.ui.get("modelDropdownIcon");
            if (modelMenu && !modelMenu.classList.contains("invisible")) {
                modelMenu.classList.add("invisible", "opacity-0", "translate-y-2");
                modelMenu.classList.remove("visible", "opacity-100", "translate-y-0");
                if (modelIcon) modelIcon.style.transform = "rotate(0deg)";
            }

            const thinkingMenu = this.ui.get("thinkingEffortMenu");
            const thinkingIcon = this.ui.get("thinkingEffortIcon");
            if (thinkingMenu && !thinkingMenu.classList.contains("invisible")) {
                thinkingMenu.classList.add("invisible", "opacity-0", "translate-y-2");
                thinkingMenu.classList.remove("visible", "opacity-100", "translate-y-0");
                if (thinkingIcon) thinkingIcon.style.transform = "rotate(0deg)";
            }

            attachMenu.classList.remove("invisible", "opacity-0", "translate-y-2", "pointer-events-none");
            attachMenu.classList.add("visible", "opacity-100", "translate-y-0", "pointer-events-auto");
            if (attachBtn) {
                attachBtn.setAttribute("aria-expanded", "true");
            }
            if (attachIcon) {
                attachIcon.style.transform = "rotate(45deg)";
            }

            setTimeout(() => {
                if (typeof lucide !== "undefined") {
                    lucide.createIcons();
                }
            }, 10);
        };

        if (attachBtn && attachMenu) {
            attachBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                const isOpen = !attachMenu.classList.contains("invisible");
                if (isOpen) {
                    closeAttachMenu();
                } else {
                    openAttachMenu();
                }
            });
        } else if (attachBtn && fileInput) {
            attachBtn.addEventListener("click", () => fileInput.click());
        }

        // Submenu button clicks
        if (menuUploadFile && fileInputDoc) {
            menuUploadFile.addEventListener("click", (e) => {
                e.stopPropagation();
                closeAttachMenu();
                fileInputDoc.click();
            });
        }

        if (menuUploadPhoto && fileInputImg) {
            menuUploadPhoto.addEventListener("click", (e) => {
                e.stopPropagation();
                closeAttachMenu();
                fileInputImg.click();
            });
        }

        if (menuUploadCode && fileInputCode) {
            menuUploadCode.addEventListener("click", (e) => {
                e.stopPropagation();
                closeAttachMenu();
                fileInputCode.click();
            });
        }

        // Bind change events to all file inputs
        [fileInput, fileInputDoc, fileInputImg, fileInputCode].forEach((input) => {
            if (input) {
                input.addEventListener("change", (e) => this.handleFileSelect(e));
            }
        });

        // Close on outside click
        document.addEventListener("click", (e) => {
            if (!attachMenu) return;
            const isClickInside =
                (attachBtn && attachBtn.contains(e.target)) ||
                attachMenu.contains(e.target);
            if (!isClickInside) {
                closeAttachMenu();
            }
        });

        // Close on escape key
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                closeAttachMenu();
            }
        });

        // Global Paste Listener (Ctrl+V / Cmd+V)
        window.addEventListener("paste", (e) => this.handlePaste(e));

        // Drag and Drop Listeners
        window.addEventListener("dragenter", (e) => {
            e.preventDefault();
            this.dragCounter++;
            this.ui.showDragOverlay(true);
        });

        window.addEventListener("dragover", (e) => {
            e.preventDefault();
        });

        window.addEventListener("dragleave", (e) => {
            e.preventDefault();
            this.dragCounter--;
            if (this.dragCounter <= 0) {
                this.dragCounter = 0;
                this.ui.showDragOverlay(false);
            }
        });

        window.addEventListener("drop", (e) => this.handleDrop(e));
    }

    handlePaste(e) {
        const clipboardData = e.clipboardData || window.clipboardData;
        if (!clipboardData) return;

        const items = clipboardData.items;
        const files = [];

        if (items) {
            for (let i = 0; i < items.length; i++) {
                if (items[i].type && items[i].type.indexOf("image") !== -1) {
                    const blob = items[i].getAsFile();
                    if (blob) {
                        files.push(blob);
                    }
                }
            }
        }

        if (files.length === 0 && clipboardData.files && clipboardData.files.length > 0) {
            for (let i = 0; i < clipboardData.files.length; i++) {
                files.push(clipboardData.files[i]);
            }
        }

        if (files.length > 0) {
            e.preventDefault();
            this.uploadAndStageFiles(files);
        }
    }

    handleDrop(e) {
        e.preventDefault();
        this.dragCounter = 0;
        this.ui.showDragOverlay(false);

        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            this.uploadAndStageFiles(Array.from(e.dataTransfer.files));
        }
    }

    handleFileSelect(e) {
        const files = Array.from(e.target.files || []);
        if (files.length > 0) {
            this.uploadAndStageFiles(files);
        }
        e.target.value = "";
    }

    async uploadAndStageFiles(files) {
        if (!files || files.length === 0) return;

        // Add temporary uploading chips
        const tempIds = [];
        files.forEach((file) => {
            const tempId = "temp_" + Math.random().toString(36).substr(2, 9);
            tempIds.push(tempId);
            this.state.stagedAttachments.push({
                id: tempId,
                name: file.name || "Pasted Image",
                size_formatted: file.size
                    ? file.size > 1048576
                        ? (file.size / 1048576).toFixed(1) + " MB"
                        : Math.round(file.size / 1024) + " KB"
                    : "",
                is_image: file.type ? file.type.startsWith("image/") : true,
                isUploading: true,
                url:
                    file.type && file.type.startsWith("image/")
                        ? URL.createObjectURL(file)
                        : null,
            });
        });

        this.ui.renderAttachmentTray(this.state.stagedAttachments, (index) =>
            this.removeStagedAttachment(index),
        );

        try {
            const result = await this.api.uploadFiles(files);
            if (result.success && result.attachments) {
                // Remove temp attachments
                tempIds.forEach((tempId) => {
                    const idx = this.state.stagedAttachments.findIndex(
                        (a) => a.id === tempId,
                    );
                    if (idx !== -1) {
                        this.state.stagedAttachments.splice(idx, 1);
                    }
                });
                // Add real uploaded attachments
                result.attachments.forEach((att) => {
                    this.state.stagedAttachments.push(att);
                });
            } else {
                // Remove temp attachments on error
                tempIds.forEach((tempId) => {
                    const idx = this.state.stagedAttachments.findIndex(
                        (a) => a.id === tempId,
                    );
                    if (idx !== -1) {
                        this.state.stagedAttachments.splice(idx, 1);
                    }
                });
                alert(result.message || "Gagal mengunggah file.");
            }
        } catch (err) {
            tempIds.forEach((tempId) => {
                const idx = this.state.stagedAttachments.findIndex(
                    (a) => a.id === tempId,
                );
                if (idx !== -1) {
                    this.state.stagedAttachments.splice(idx, 1);
                }
            });
            alert("Gagal mengunggah file: " + err.message);
        }

        this.ui.renderAttachmentTray(this.state.stagedAttachments, (index) =>
            this.removeStagedAttachment(index),
        );
    }

    removeStagedAttachment(index) {
        this.state.stagedAttachments.splice(index, 1);
        this.ui.renderAttachmentTray(this.state.stagedAttachments, (i) =>
            this.removeStagedAttachment(i),
        );
    }

    async handleSubmit(e) {
        e.preventDefault();

        const input = this.ui.get("messageInput");
        const text = input?.value.trim() || "";
        const attachments = [
            ...this.state.stagedAttachments.filter((a) => !a.isUploading),
        ];

        if ((!text && attachments.length === 0) || this.state.isTyping) return;

        this.ui.get("welcomeState")?.classList.add("hidden");
        this.ui.get("messages")?.classList.remove("hidden");

        this.ui.addUserMessage(text, attachments);
        this.state.addMessage("user", text, attachments);

        input.value = "";
        this.state.stagedAttachments = [];
        this.ui.renderAttachmentTray([], null);
        this.ui.setLoading(true);

        const typing = this.ui.showTypingIndicator();

        try {
            const result = await this.api.sendMessage(
                text,
                this.state.selectedModel,
                this.state.sessionId,
                attachments,
            );
            typing.remove();

            if (result.ok && result.data.reply) {
                this.state.addMessage("assistant", result.data.reply);
                const messageIndex = this.state.messages.length - 1;
                const assistantMsg = this.ui.addAssistantMessage(
                    "",
                    messageIndex,
                );
                if (assistantMsg) {
                    await this.streamAssistantMessage(assistantMsg, result.data.reply);
                }

                if (result.data.session_id) {
                    this.state.setSessionId(result.data.session_id);
                    this.loadChatHistory();
                }
            } else {
                this.ui.showError(result.data);
            }
        } catch (error) {
            typing.remove();
            this.ui.showError({ message: error.message });
        }

        this.ui.setLoading(false);
        input?.focus();
    }

    newChat() {
        this.stopTyping();
        this.ui.clearChat();
        this.state.sessionId = null;
        this.state.clearStagedAttachments();
        this.ui.renderAttachmentTray([], null);
        Utils.setStorage("last_active_session_id", null);
        this.state.clearMessages();
    }

    stopTyping() {
        if (this.state.typingAnimation) {
            clearInterval(this.state.typingAnimation);
            this.state.typingAnimation = null;
        }
        this.state.isTyping = false;
    }

    streamAssistantMessage(element, fullText) {
        return new Promise((resolve) => {
            this.state.isTyping = true;
            this.ui.setLoading(true);

            let currentText = "";
            let charIndex = 0;
            const chunkSize = 1; // 1 char per tick for realistic typing

            const typeInterval = setInterval(() => {
                if (!this.state.isTyping) {
                    clearInterval(typeInterval);
                    this.markdown.render(element, fullText, false);
                    resolve();
                    return;
                }

                currentText += fullText.substring(charIndex, charIndex + chunkSize);
                charIndex += chunkSize;

                this.markdown.render(element, currentText, true);

                if (charIndex >= fullText.length) {
                    clearInterval(typeInterval);
                    this.markdown.render(element, fullText, false);
                    this.state.isTyping = false;
                    resolve();
                }
            }, 15); // Faster tick, smaller chunk

            this.state.typingAnimation = typeInterval;
        });
    }

    async loadChatHistory() {
        let result;
        if (window.chatConfig && window.chatConfig.initialHistory && !this.initialHistoryLoaded) {
            result = { sessions: window.chatConfig.initialHistory };
            this.initialHistoryLoaded = true;
        } else {
            result = await this.api.getHistory();
        }

        const list = this.ui.get("chatHistoryList");
        const container = this.ui.get("chatHistoryContainer");

        if (!container) return;

        if (!result.sessions || result.sessions.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-xs text-gray-400">
                    <i data-lucide="message-square" class="h-6 w-6 mx-auto mb-2 opacity-50"></i>
                    <p>Belum ada riwayat chat</p>
                </div>
            `;
            if (typeof lucide !== "undefined") lucide.createIcons();
            return;
        }

        container.innerHTML = ""; // Clear existing

        // Save original sessions to state for search filtering
        this.state.sessions = result.sessions;

        this.renderFilteredHistory(result.sessions);
    }

    renderFilteredHistory(sessionsToRender) {
        const container = this.ui.get("chatHistoryContainer");
        if (!container) return;
        
        container.innerHTML = "";
        
        if (!sessionsToRender || sessionsToRender.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-xs text-gray-400">
                    <i data-lucide="search-x" class="h-6 w-6 mx-auto mb-2 opacity-50"></i>
                    <p>Pencarian tidak ditemukan</p>
                </div>
            `;
            if (typeof lucide !== "undefined") lucide.createIcons();
            return;
        }

        const pinnedSessions = sessionsToRender.filter(s => s.is_pinned);
        const recentSessions = sessionsToRender.filter(s => !s.is_pinned);

        const renderSection = (title, sessions) => {
            if (sessions.length === 0) return;
            
            const sectionTitle = document.createElement("div");
            sectionTitle.className = "px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1 mt-2";
            sectionTitle.textContent = title;
            container.appendChild(sectionTitle);

            sessions.forEach((session) => {
                const itemWrapper = document.createElement("div");
                itemWrapper.className = "relative group chat-session-wrapper";
                itemWrapper.setAttribute("data-session-id", session.id);

                // Main clickable area
                const mainBtn = document.createElement("button");
                mainBtn.className = "chat-session-item w-full text-left px-3 py-2 pr-8 rounded-lg hover:bg-gray-100 dark:hover:bg-[#202222] transition text-sm flex flex-col justify-center";
                mainBtn.title = session.title;
                
                const titleDiv = document.createElement("div");
                titleDiv.className = "font-medium text-gray-700 dark:text-gray-300 truncate w-full session-title";
                titleDiv.textContent = session.title;

                const dateDiv = document.createElement("div");
                dateDiv.className = "text-xs text-gray-400 truncate";
                dateDiv.textContent = Utils.formatDate(session.updated_at || session.created_at);

                mainBtn.appendChild(titleDiv);
                mainBtn.appendChild(dateDiv);
                
                mainBtn.addEventListener("click", (e) => {
                    e.preventDefault();
                    if (!itemWrapper.classList.contains("editing")) {
                        this.loadChatSession(session.id);
                    }
                });

                // Options Button (3-dots)
                const optionsBtn = document.createElement("button");
                optionsBtn.className = "absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-md text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-[#333538] opacity-0 group-hover:opacity-100 transition-opacity z-10 focus:opacity-100";
                optionsBtn.innerHTML = '<i data-lucide="more-vertical" class="w-4 h-4"></i>';
                
                // Dropdown Menu
                const dropdown = document.createElement("div");
                dropdown.className = "absolute right-0 top-10 w-48 bg-white dark:bg-[#1e1f20] border border-gray-200 dark:border-[#333538] rounded-xl shadow-xl z-50 hidden flex-col py-1 text-sm overflow-hidden";
                
                const createMenuItem = (icon, text, onClick) => {
                    const btn = document.createElement("button");
                    btn.className = "w-full text-left px-4 py-2 flex items-center gap-3 hover:bg-gray-100 dark:hover:bg-[#2c2d30] text-gray-700 dark:text-gray-200 transition-colors";
                    btn.innerHTML = `<i data-lucide="${icon}" class="w-4 h-4"></i> <span>${text}</span>`;
                    btn.addEventListener("click", (e) => {
                        e.stopPropagation();
                        dropdown.classList.add("hidden");
                        onClick();
                    });
                    return btn;
                };

                dropdown.appendChild(createMenuItem(session.is_pinned ? "pin-off" : "pin", session.is_pinned ? "Lepaskan sematan" : "Sematkan", async () => {
                    await this.api.pinSession(session.id);
                    this.loadChatHistory(); // reload to reorder
                }));

                dropdown.appendChild(createMenuItem("pencil", "Ganti nama", () => {
                    itemWrapper.classList.add("editing");
                    optionsBtn.classList.add("hidden"); // Sembunyikan titik 3 saat edit
                    
                    const input = document.createElement("input");
                    input.type = "text";
                    input.value = session.title;
                    input.className = "w-full bg-transparent border-b-2 border-emerald-500 font-medium text-gray-900 dark:text-white outline-none px-0 py-0 m-0 leading-tight";
                    
                    const saveRename = async () => {
                        const newTitle = input.value.trim();
                        if (newTitle && newTitle !== session.title) {
                            await this.api.renameSession(session.id, newTitle);
                            titleDiv.textContent = newTitle;
                            session.title = newTitle;
                            // Update internal state title so search still works correctly
                            const sessionInState = this.state.sessions.find(s => s.id === session.id);
                            if (sessionInState) sessionInState.title = newTitle;
                        }
                        mainBtn.replaceChild(titleDiv, input);
                        itemWrapper.classList.remove("editing");
                        optionsBtn.classList.remove("hidden"); // Tampilkan kembali titik 3
                    };

                    input.addEventListener("blur", saveRename);
                    input.addEventListener("keydown", (e) => {
                        if (e.key === "Enter") saveRename();
                        if (e.key === "Escape") {
                            mainBtn.replaceChild(titleDiv, input);
                            itemWrapper.classList.remove("editing");
                            optionsBtn.classList.remove("hidden");
                        }
                    });

                    mainBtn.replaceChild(input, titleDiv);
                    input.focus();
                    input.select();
                }));

                dropdown.appendChild(createMenuItem("trash-2", "Hapus", () => {
                    this.deleteSession(session.id);
                }));

                optionsBtn.addEventListener("click", (e) => {
                    e.stopPropagation();
                    // Close all other dropdowns
                    document.querySelectorAll('.chat-session-wrapper .absolute.flex-col').forEach(el => {
                        if (el !== dropdown) el.classList.add("hidden");
                    });
                    dropdown.classList.toggle("hidden");
                });

                itemWrapper.appendChild(mainBtn);
                itemWrapper.appendChild(optionsBtn);
                itemWrapper.appendChild(dropdown);
                container.appendChild(itemWrapper);
            });
        };

        renderSection("Disematkan", pinnedSessions);
        renderSection("Terbaru", recentSessions);

        if (typeof lucide !== "undefined") {
            lucide.createIcons();
        }

        // Close dropdowns on outside click
        document.addEventListener("click", (e) => {
            if (!e.target.closest('.chat-session-wrapper')) {
                document.querySelectorAll('.chat-session-wrapper .absolute.flex-col').forEach(el => {
                    el.classList.add("hidden");
                });
            }
        });
    }

    async loadChatSession(sessionId) {
        const result = await this.api.getChatSession(sessionId);
        if (!result || !result.session) return;

        this.state.setSessionId(sessionId);
        Utils.setStorage("last_active_session_id", sessionId);
        this.ui.clearChat();
        this.state.clearMessages();
        this.state.clearStagedAttachments();
        this.ui.renderAttachmentTray([], null);

        // Restore saved model
        const savedModel = Utils.getStorage(`nusa-model-sess-${sessionId}`) || result.session.model_used;
        if (savedModel) {
            this.state.selectedModel = savedModel;
            Utils.setStorage(CONFIG.STORAGE_KEYS.DEFAULT_MODEL, savedModel);
            this.ui.updateModelDisplay(this.state.selectedModel, this.modelData);
        }

        // Restore saved effort
        const savedEffort = Utils.getStorage(`nusa-effort-sess-${sessionId}`);
        if (savedEffort) {
            this.state.thinkingEffort = savedEffort;
            Utils.setStorage(CONFIG.STORAGE_KEYS.THINKING_EFFORT, savedEffort);
            this.updateThinkingEffortDisplay(savedEffort);
        }

        if (result.messages) {
            result.messages.forEach((msg, index) => {
                this.state.addMessage(msg.role, msg.content, msg.attachments);
                if (msg.role === "user") {
                    this.ui.addUserMessage(msg.content, msg.attachments);
                } else if (msg.role === "assistant") {
                    const assistantMsg = this.ui.addAssistantMessage(
                        msg.content,
                        index,
                    );
                    if (assistantMsg)
                        this.markdown.render(assistantMsg, msg.content);
                }
            });
        }

        this.ui.get("welcomeState")?.classList.add("hidden");
        this.ui.get("messages")?.classList.remove("hidden");
    }

    async deleteSession(sessionId) {
        const result = await this.api.deleteSession(sessionId);
        if (result.success !== false) {
            if (this.state.sessionId === sessionId) this.newChat();
            this.loadChatHistory();
        }
    }

    toggleDarkMode() {
        const isDark = this.state.toggleDarkMode();
        this.ui.setDarkMode(isDark);
        const toggle = this.ui.get("darkModeToggle");
        if (toggle) toggle.setAttribute("aria-checked", isDark.toString());
    }

    applyDarkMode() {
        this.ui.setDarkMode(this.state.darkMode);
        const toggle = this.ui.get("darkModeToggle");
        if (toggle)
            toggle.setAttribute("aria-checked", this.state.darkMode.toString());
    }

    // Helper method to re-render existing messages
    renderExistingMessages() {
        this.ui.clearChat();

        if (this.state.messages.length > 0) {
            this.ui.get("welcomeState")?.classList.add("hidden");
            this.ui.get("messages")?.classList.remove("hidden");
        }

        this.state.messages.forEach((msg, i) => {
            if (msg.role === "user") {
                this.ui.addUserMessage(msg.content, msg.attachments);
            } else {
                const assistantMsg = this.ui.addAssistantMessage(
                    msg.content,
                    i,
                );
                if (assistantMsg)
                    this.markdown.render(assistantMsg, msg.content);
            }
        });
    }

    // Message actions
    editMessage(index) {
        let targetIndex = index;
        if (this.state.messages[index]?.role === "assistant") {
            for (let i = index - 1; i >= 0; i--) {
                if (this.state.messages[i].role === "user") {
                    targetIndex = i;
                    break;
                }
            }
        }
        
        const message = this.state.messages[targetIndex];
        if (!message || message.role !== "user") return;

        const input = this.ui.get("messageInput");
        if (input) {
            input.value = message.content;
            input.focus();
            this.state.messages = this.state.messages.slice(0, targetIndex);
            
            // Delete messages from backend if in a saved session
            if (this.state.sessionId) {
                this.api.truncateMessages(this.state.sessionId, targetIndex);
            }

            this.renderExistingMessages();
        }
    }

    async regenerateMessage(index) {
        let userIndex = -1;
        if (this.state.messages[index]?.role === "assistant") {
            for (let i = index - 1; i >= 0; i--) {
                if (this.state.messages[i].role === "user") {
                    userIndex = i;
                    break;
                }
            }
        } else if (this.state.messages[index]?.role === "user") {
            userIndex = index;
        }

        if (userIndex === -1) {
            userIndex = this.state.getLastUserMessageIndex();
        }
        if (userIndex === -1) return;

        const userMessage = this.state.messages[userIndex];
        this.state.messages = this.state.messages.slice(0, userIndex + 1);
        
        // Delete messages after userIndex from backend if in a saved session
        // (we truncate starting from userIndex + 1 since we keep the user's prompt)
        if (this.state.sessionId) {
            this.api.truncateMessages(this.state.sessionId, userIndex + 1);
        }

        this.renderExistingMessages();

        this.ui.setLoading(true);
        const typing = this.ui.showTypingIndicator();

        try {
            const result = await this.api.sendMessage(
                userMessage.content,
                this.state.selectedModel,
                this.state.sessionId,
                userMessage.attachments,
            );
            if (typing) typing.remove();

            if (result.ok && result.data.reply) {
                this.state.addMessage("assistant", result.data.reply);
                const messageIndex = this.state.messages.length - 1;
                const assistantMsg = this.ui.addAssistantMessage(
                    "",
                    messageIndex,
                );
                if (assistantMsg) {
                    await this.streamAssistantMessage(assistantMsg, result.data.reply);
                }
            } else {
                this.ui.showError(result.data || { message: "Unknown error" });
            }
        } catch (error) {
            if (typing) typing.remove();
            this.ui.showError({ message: error.message });
        }

        this.ui.setLoading(false);
    }

    copyMessage(index) {
        const message = this.state.messages[index];
        if (!message) return;

        navigator.clipboard
            .writeText(message.content)
            .then(() => {
                const row = document.querySelector(`[data-message-index="${index}"]`);
                if (!row) return;
                const btn = row.querySelector('[data-action="copy"]');
                if (!btn) return;

                const originalIcon = btn.innerHTML;
                btn.innerHTML =
                    '<i data-lucide="check" class="h-3.5 w-3.5 text-green-500"></i>';
                if (typeof lucide !== "undefined") lucide.createIcons();
                setTimeout(() => {
                    btn.innerHTML = originalIcon;
                    if (typeof lucide !== "undefined") lucide.createIcons();
                }, CONFIG.COPY_FEEDBACK_DURATION);
            })
            .catch(() => {
                // Silent fail for copy errors
            });
    }

    copyCode(button) {
        this.markdown.copyCode(button);
    }

    sendSuggestion(text) {
        const input = this.ui.get("messageInput");
        if (input) {
            input.value = text;
            input.focus();
            // Auto-submit the suggestion
            this.ui
                .get("chatForm")
                ?.dispatchEvent(new Event("submit", { cancelable: true }));
        }
    }

    showDeleteConfirm() {
        const modal = document.getElementById("deleteConfirmModal");
        if (modal) {
            modal.classList.remove("hidden");
            modal.style.display = "flex";
        }
    }

    hideDeleteConfirm() {
        const modal = document.getElementById("deleteConfirmModal");
        if (modal) {
            modal.classList.add("hidden");
            modal.style.display = "none";
        }
    }

    confirmDeleteChat() {
        if (this.state.sessionId) {
            this.deleteSession(this.state.sessionId);
        }
        this.hideDeleteConfirm();
    }

    openStatusModal() {
        const modal = document.getElementById("statusModal");
        if (modal) {
            modal.classList.remove("hidden");
            this.loadStatusModal();
        }
    }

    closeStatusModal() {
        const modal = document.getElementById("statusModal");
        if (modal) {
            modal.classList.add("hidden");
        }
    }

    async manualHeartbeat() {
        const statusLoading = document.getElementById('statusLoading');
        const statusContent = document.getElementById('statusContent');
        const statusList = document.getElementById('statusList');

        const btn = event?.target?.closest("button");
        let originalHTML = "";
        if (btn) {
            originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML =
                '<i data-lucide="loader" class="h-4 w-4 animate-spin"></i> Memeriksa...';
            if (typeof lucide !== "undefined") lucide.createIcons();
        }

        // Show loading stably while checking
        if (statusLoading && statusContent) {
            statusLoading.classList.remove('hidden');
            statusContent.classList.add('hidden');
        }

        try {
            // Trigger actual heartbeat check to update model statuses
            await fetch('/api/ai/heartbeat', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            // Fetch and render updated statuses
            await this.loadStatusModal(false);
        } catch (error) {
            console.error('Failed to run heartbeat:', error);
            if (statusLoading && statusContent && statusList) {
                statusLoading.classList.add('hidden');
                statusContent.classList.remove('hidden');
                statusList.innerHTML = `
                    <div class="text-center py-6">
                        <i data-lucide="triangle-alert" class="h-8 w-8 text-amber-500 mx-auto mb-2"></i>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Gagal memperbarui status</p>
                        <p class="text-xs text-gray-400 mt-1">${error.message || 'Periksa koneksi internet'}</p>
                    </div>
                `;
                if (typeof lucide !== "undefined") lucide.createIcons();
            }
        } finally {
            if (btn) {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                if (typeof lucide !== "undefined") lucide.createIcons();
            }
        }
    }

    async loadStatusModal(triggerHeartbeatIfEmpty = false) {
        const statusLoading = document.getElementById('statusLoading');
        const statusContent = document.getElementById('statusContent');
        const statusList = document.getElementById('statusList');

        if (!statusLoading || !statusContent || !statusList) {
            console.error('Status modal elements not found');
            return;
        }

        // Show loading stably, hide content
        statusLoading.classList.remove('hidden');
        statusContent.classList.add('hidden');

        try {
            let response = await fetch('/api/ai/status', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            let result = await response.json();
            if (!result.success) {
                throw new Error(result.message || 'API returned success=false');
            }

            let statuses = result.statuses || result.data || [];

            // If empty and allowed, trigger heartbeat once
            if (statuses.length === 0 && triggerHeartbeatIfEmpty) {
                await fetch('/api/ai/heartbeat', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                response = await fetch('/api/ai/status', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                result = await response.json();
                statuses = result.statuses || result.data || [];
            }

            // Hide loading, show content once fully ready
            statusLoading.classList.add('hidden');
            statusContent.classList.remove('hidden');

            if (statuses.length === 0) {
                statusList.innerHTML = `
                    <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                        <p class="text-sm">Belum ada data status AI.</p>
                    </div>
                `;
                return;
            }

            // Display statuses cleanly
            statusList.innerHTML = `
                <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                    ${statuses.map(status => `
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-[#191A1A] border border-gray-100 dark:border-[#2F3030] rounded-xl transition-all">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full shrink-0 ${status.is_online ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400' : 'bg-red-100 dark:bg-red-950/60 text-red-600 dark:text-red-400'}">
                                    <i data-lucide="${status.is_online ? 'check' : 'x'}" class="h-4 w-4"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">${status.display_name || status.model_name}</p>
                                    ${status.last_check_at ? `<p class="text-[11px] text-gray-500 dark:text-gray-400">${status.last_check_at}</p>` : ''}
                                </div>
                            </div>
                            <div class="text-right shrink-0 ml-2">
                                ${status.response_time_ms ? `<p class="text-xs font-medium text-gray-700 dark:text-gray-300 font-mono">${status.response_time_ms}ms</p>` : ''}
                                ${!status.is_online && status.last_error ? `<p class="text-[11px] text-red-500 truncate max-w-[130px]" title="${status.last_error}">Offline</p>` : ''}
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;

            if (typeof lucide !== 'undefined') lucide.createIcons();

        } catch (error) {
            console.error('Failed to load AI status:', error);

            // Hide loading, show content with error
            statusLoading.classList.add('hidden');
            statusContent.classList.remove('hidden');

            statusList.innerHTML = `
                <div class="text-center py-8">
                    <i data-lucide="triangle-alert" class="h-8 w-8 text-red-500 mx-auto mb-3"></i>
                    <p class="text-sm text-gray-600 dark:text-gray-300 font-medium">Gagal memuat status AI</p>
                    <p class="text-xs text-gray-400 mt-1 mb-4">${error.message || 'Terjadi kesalahan'}</p>
                    <button onclick="window.chatBot?.loadStatusModal(false)" class="px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:opacity-90 transition-colors">
                        Coba Lagi
                    </button>
                </div>
            `;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    async exportConversation() {
        if (!this.state.sessionId) {
            // Export chat as JSON manually if no session saved yet
            const data = {
                session_id: this.state.sessionId || "unsaved",
                messages: this.state.messages,
                exported_at: new Date().toISOString(),
            };

            const blob = new Blob([JSON.stringify(data, null, 2)], {
                type: "application/json",
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = `chat-new-${Date.now()}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            return;
        }

        // Hit API for full export data
        try {
            const response = await fetch(
                `/api/chat/session/${this.state.sessionId}/export`,
                {
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN":
                            document.querySelector('meta[name="csrf-token"]')
                                ?.content || "",
                    },
                },
            );
            const result = await response.json();

            if (result.success) {
                const exportData = {
                    session: result.session,
                    messages: result.messages,
                    exported_at: result.exported_at
                };
                const blob = new Blob([JSON.stringify(exportData, null, 2)], {
                    type: "application/json",
                });
                const url = URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = `chat-${result.session.title.replace(/[^a-z0-9]/gi, '_').toLowerCase()}-${Date.now()}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            } else {
                console.error("Export failed:", result);
            }
        } catch (e) {
            console.error("Export error:", e);
        }
    }
}

window.sendSuggestion = function (text) {
    if (window.chatBot && typeof window.chatBot.sendSuggestion === "function") {
        window.chatBot.sendSuggestion(text);
    }
    // Silent fail - ChatBot not initialized
};

window.toggleChatSidebar = function (open) {
    if (
        window.chatBot &&
        window.chatBot.ui &&
        typeof window.chatBot.ui.toggleSidebar === "function"
    ) {
        window.chatBot.ui.toggleSidebar(open);
    }
};

window.toggleDarkMode = function () {
    if (window.chatBot && typeof window.chatBot.toggleDarkMode === "function") {
        window.chatBot.toggleDarkMode();
    }
};

window.showDeleteConfirm = function () {
    if (
        window.chatBot &&
        typeof window.chatBot.showDeleteConfirm === "function"
    ) {
        window.chatBot.showDeleteConfirm();
    }
};

window.hideDeleteConfirm = function () {
    if (
        window.chatBot &&
        typeof window.chatBot.hideDeleteConfirm === "function"
    ) {
        window.chatBot.hideDeleteConfirm();
    }
};

window.confirmDeleteChat = function () {
    if (
        window.chatBot &&
        typeof window.chatBot.confirmDeleteChat === "function"
    ) {
        window.chatBot.confirmDeleteChat();
    }
};

window.openStatusModal = function () {
    if (
        window.chatBot &&
        typeof window.chatBot.openStatusModal === "function"
    ) {
        window.chatBot.openStatusModal();
    }
};

window.closeStatusModal = function () {
    if (
        window.chatBot &&
        typeof window.chatBot.closeStatusModal === "function"
    ) {
        window.chatBot.closeStatusModal();
    }
};

window.manualHeartbeat = function () {
    if (
        window.chatBot &&
        typeof window.chatBot.manualHeartbeat === "function"
    ) {
        window.chatBot.manualHeartbeat();
    }
};

window.exportConversation = function () {
    if (
        window.chatBot &&
        typeof window.chatBot.exportConversation === "function"
    ) {
        window.chatBot.exportConversation();
    }
};

window.stopTyping = function () {
    if (window.chatBot && typeof window.chatBot.stopTyping === "function") {
        window.chatBot.stopTyping();
    }
};

window.searchChat = function (query) {
    const q = (query || "").trim().toLowerCase();
    const list = document.getElementById("chatHistoryList");
    if (list) {
        const items = list.querySelectorAll("button.chat-session-item, button[data-session-id]");
        items.forEach((item) => {
            const title = (item.getAttribute("data-title") || item.textContent || "").toLowerCase();
            if (!q || title.includes(q)) {
                item.style.display = "";
            } else {
                item.style.display = "none";
            }
        });
    }

    if (window.modernFeatures && typeof window.modernFeatures.debounceSearch === "function") {
        window.modernFeatures.debounceSearch(query);
    }
};

class ModernFeatures {
    constructor() {
        this.autoSaveTimer = null;
        this.searchTimer = null;
        this.init();
    }

    init() {
        this.setupKeyboardShortcuts();
        this.setupAutoSave();
        this.setupSearchUI();
    }

    // Keyboard Shortcuts
    setupKeyboardShortcuts() {
        document.addEventListener("keydown", (e) => {
            // Ctrl/Cmd + Enter = Send
            if ((e.ctrlKey || e.metaKey) && e.key === "Enter") {
                e.preventDefault();
                const input = document.querySelector(
                    '[data-selector="messageInput"]',
                );
                if (input && document.activeElement === input) {
                    input.closest("form")?.requestSubmit();
                }
            }
            // Ctrl/Cmd + K = Search
            if ((e.ctrlKey || e.metaKey) && e.key === "k") {
                e.preventDefault();
                document.querySelector("#globalSearchInput")?.focus();
            }
            // Ctrl/Cmd + N = New Chat
            if ((e.ctrlKey || e.metaKey) && e.key === "n") {
                e.preventDefault();
                document.querySelector('[data-selector="newChatBtn"]')?.click();
            }
            // Ctrl/Cmd + E = Export
            if ((e.ctrlKey || e.metaKey) && e.key === "e") {
                e.preventDefault();
                if (window.chatBot?.state?.sessionId) {
                    this.exportChat();
                }
            }
        });
    }

    // Auto-Save Draft
    setupAutoSave() {
        const input = document.querySelector('[data-selector="messageInput"]');
        if (!input) return;

        input.addEventListener("input", () => {
            clearTimeout(this.autoSaveTimer);
            this.autoSaveTimer = setTimeout(() => {
                const text = input.value.trim();
                if (text) {
                    localStorage.setItem(
                        "nusa_draft",
                        JSON.stringify({
                            text,
                            time: Date.now(),
                        }),
                    );
                    this.showToast("Draft tersimpan", "success");
                }
            }, 1000);
        });

        // Load draft on page load
        this.loadDraft();
    }

    loadDraft() {
        try {
            const draft = localStorage.getItem("nusa_draft");
            if (!draft) return;

            const { text, time } = JSON.parse(draft);
            const hoursAgo = (Date.now() - time) / (1000 * 60 * 60);

            if (hoursAgo > 24) {
                localStorage.removeItem("nusa_draft");
                return;
            }

            const input = document.querySelector(
                '[data-selector="messageInput"]',
            );
            if (input && text) {
                input.value = text;
                this.showToast("Draft ditemukan", "info");
            }
        } catch (e) {
            console.error("Draft load error:", e);
        }
    }

    clearDraft() {
        localStorage.removeItem("nusa_draft");
    }

    // Search UI
    setupSearchUI() {
        const sidebar = document.querySelector('[data-selector="chatSidebar"]');
        if (!sidebar) return;

        let searchContainer = sidebar.querySelector(".search-container");
        if (!searchContainer) {
            searchContainer = document.createElement("div");
            searchContainer.className =
                "search-container p-3 border-b border-gray-200 dark:border-gray-700";
            searchContainer.innerHTML = `
                <div class="relative">
                    <input type="text"
                           id="globalSearchInput"
                           class="w-full px-4 py-2 pl-10 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                           placeholder="Cari chat... (Ctrl+K)"
                    />
                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            `;

            const historyContainer = sidebar.querySelector(
                '[data-selector="chatHistoryList"]',
            )?.parentElement;
            if (historyContainer) {
                historyContainer.insertBefore(
                    searchContainer,
                    historyContainer.firstChild,
                );
            }

            // Search handler
            searchContainer
                .querySelector("#globalSearchInput")
                .addEventListener("input", (e) => {
                    this.debounceSearch(e.target.value);
                });
        }
    }

    debounceSearch(value) {
        clearTimeout(this.searchTimer);
        if (value.length < 2) {
            this.clearSearchResults();
            return;
        }
        this.searchTimer = setTimeout(() => this.searchChats(value), 300);
    }

    async searchChats(query) {
        try {
            const response = await fetch(
                `/api/chat/search?q=${encodeURIComponent(query)}`,
                {
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN":
                            document.querySelector('meta[name="csrf-token"]')
                                ?.content || "",
                    },
                },
            );
            const result = await response.json();

            if (result.success) {
                this.showSearchResults(result.data, query);
            }
        } catch (e) {
            console.error("Search error:", e);
        }
    }

    showSearchResults(results, query) {
        const historyList = document.querySelector(
            '[data-selector="chatHistoryList"]',
        );
        if (!historyList) return;

        if (results.length === 0) {
            historyList.innerHTML = `<div class="p-4 text-center text-gray-500 text-sm">Tidak ada hasil untuk "${query}"</div>`;
            return;
        }

        historyList.innerHTML = results
            .map(
                (msg) => `
            <div class="search-result-item p-3 mb-2 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600"
                 data-session-id="${msg.session_id}">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">${msg.session_title}</div>
                <div class="text-sm text-gray-700 dark:text-gray-200 line-clamp-2">
                    ${this.highlightText(msg.content, query)}
                </div>
                <div class="text-xs text-gray-400 mt-1">${this.formatDate(msg.created_at)}</div>
            </div>
        `,
            )
            .join("");

        historyList.querySelectorAll(".search-result-item").forEach((item) => {
            item.addEventListener("click", () => {
                const sessionId = item.dataset.sessionId;
                if (window.chatBot) {
                    window.chatBot.loadChatSession(sessionId);
                }
            });
        });
    }

    highlightText(text, query) {
        const regex = new RegExp(`(${query})`, "gi");
        return text.replace(
            regex,
            '<mark class="bg-yellow-200 dark:bg-yellow-700 px-1 rounded">$1</mark>',
        );
    }

    clearSearchResults() {
        if (window.chatBot) {
            window.chatBot.loadChatHistory();
        }
    }

    // Delete Message & Copy
    setupMessageActions() {
        document.addEventListener("mouseover", (e) => {
            const messageRow = e.target.closest("[data-message-id]");
            if (!messageRow || messageRow.querySelector(".message-actions"))
                return;

            const actions = document.createElement("div");
            actions.className =
                "message-actions absolute right-2 top-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity";
            actions.innerHTML = `
                <button class="p-1 hover:bg-gray-200 dark:hover:bg-gray-700 rounded" title="Copy" onclick="window.modernFeatures.copyMessage(this)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </button>
                <button class="p-1 hover:bg-red-100 dark:hover:bg-red-900 rounded text-red-500" title="Delete" onclick="window.modernFeatures.deleteMessage(this)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            `;

            messageRow.classList.add("group", "relative");
            messageRow.appendChild(actions);
        });
    }

    async deleteMessage(btn) {
        const messageRow = btn.closest("[data-message-id]");
        const messageId = messageRow.dataset.messageId;
        const sessionId = window.chatBot?.state?.sessionId;

        if (!confirm("Hapus pesan ini?")) return;

        try {
            const response = await fetch(
                `/api/chat/session/${sessionId}/message/${messageId}`,
                {
                    method: "DELETE",
                    headers: {
                        "X-CSRF-TOKEN":
                            document.querySelector('meta[name="csrf-token"]')
                                ?.content || "",
                    },
                },
            );
            const result = await response.json();

            if (result.success) {
                messageRow.remove();
                this.showToast("Pesan dihapus", "success");
            } else {
                this.showToast("Gagal menghapus pesan", "error");
            }
        } catch (e) {
            console.error("Delete error:", e);
            this.showToast("Gagal menghapus pesan", "error");
        }
    }

    copyMessage(btn) {
        const messageRow = btn.closest("[data-message-id]");
        const content =
            messageRow.querySelector(".message-content")?.textContent;
        if (!content) return;

        navigator.clipboard.writeText(content);
        this.showToast("Copied!", "success");
    }

    // Export Chat
    async exportChat() {
        if (window.chatBot && typeof window.chatBot.exportConversation === "function") {
            window.chatBot.exportConversation();
        }
    }

    downloadFile(content, filename, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    // Toast Notifications
    showToast(message, type = "info") {
        const existing = document.querySelector(".modern-toast");
        if (existing) existing.remove();

        const toast = document.createElement("div");
        toast.className = `modern-toast fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white text-sm z-50 ${
            type === "success"
                ? "bg-green-500"
                : type === "error"
                  ? "bg-red-500"
                  : "bg-blue-500"
        }`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = "all 0.3s";
            toast.style.opacity = "0";
            toast.style.transform = "translateY(10px)";
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Date Formatter
    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const hours = Math.floor((now - date) / (1000 * 60 * 60));

        if (hours < 1) return "Baru saja";
        if (hours < 24) return `${hours}j yang lalu`;
        return date.toLocaleDateString("id-ID", {
            day: "numeric",
            month: "short",
        });
    }
}

// Initialize Modern Features
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
        window.chatBot = new ChatBot();
        window.modernFeatures = new ModernFeatures();
    });
} else {
    window.chatBot = new ChatBot();
    window.modernFeatures = new ModernFeatures();
}
