<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'NUSA — AI Assistant')</title>
    <link rel="icon" href="data:,">

    {{-- Prevent FOUC (Flash of Unstyled Content) for Dark Mode --}}
    <script>
        try {
            const storedTheme = localStorage.getItem('nusa-dark-mode');
            if (storedTheme === 'true' || (storedTheme === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {}
    </script>

    {{-- Tailwind CSS is loaded via Vite below --}}

    {{-- Google Fonts Collection --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,300..700;1,300..700&display=swap" rel="stylesheet">

    {{-- Lucide Icons CDN --}}
    <script src="https://unpkg.com/lucide@latest"></script>

    {{-- Markdown-it for better Markdown parsing --}}
    <script src="https://cdn.jsdelivr.net/npm/markdown-it@14.1.0/dist/markdown-it.min.js"></script>

    {{-- markdown-it-prism plugin for automatic code block highlighting --}}
    <script src="https://unpkg.com/markdown-it-prism@2.3.0/dist/markdown-it-prism.min.js"></script>

    {{-- Prism.js for beautiful Syntax Highlighting --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-typescript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-json.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-bash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-html.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-clike.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-sql.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-java.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-c.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-cpp.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-csharp.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-go.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-rust.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-markdown.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-xml-doc.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-jsx.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-tsx.min.js"></script>

    {{-- Animate.css for smooth animations --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <script>
        // Initialize markdown-it with prism plugin
        window.md = null;
        if (typeof markdownit !== 'undefined' && typeof markdownItPrism !== 'undefined') {
            window.md = markdownit({
                html: true,
                linkify: true,
                typographer: true,
                breaks: true
            }).use(markdownItPrism);
            console.log('✓ markdown-it + prism plugin loaded successfully');
        } else if (typeof markdownit !== 'undefined') {
            window.md = markdownit({
                html: true,
                linkify: true,
                typographer: true,
                breaks: true
            });
            console.log('✓ markdown-it loaded (without prism plugin)');
        } else {
            console.error('✗ markdown-it NOT loaded!');
        }

        // Test if Prism loaded
        if (typeof Prism !== 'undefined') {
            console.log('✓ Prism.js loaded successfully');
        } else {
            console.error('✗ Prism.js NOT loaded!');
        }

        // Tailwind CSS configuration is handled in resources/css/app.css
    </script>

    <style>
        :root, body, input, select, textarea, button {
            font-family: 'JetBrains Mono', monospace !important;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Typing Indicator */
        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .typing-indicator span {
            width: 6px;
            height: 6px;
            border-radius: 9999px;
            background-color: #9ca3af;
            animation: typing-bounce 1.2s infinite ease-in-out;
        }

        .typing-indicator span:nth-child(2) {
            animation-delay: 0.15s;
        }

        .typing-indicator span:nth-child(3) {
            animation-delay: 0.3s;
        }

        @keyframes typing-bounce {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.4;
            }
            30% {
                transform: translateY(-4px);
                opacity: 1;
            }
        }

        /* Code Block Styles */
        .code-block-wrapper {
            margin: 1rem 0;
            border-radius: 0.5rem;
            overflow: hidden;
            background-color: #1a1a2e;
        }

        .code-block-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 1rem;
            background-color: #16162a;
            border-bottom: 1px solid #2a2a3e;
        }

        .code-block-language {
            font-size: 0.75rem;
            color: #a0a0b0;
            text-transform: uppercase;
            font-weight: 500;
        }

        .copy-code-btn {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            color: #a0a0b0;
            background-color: transparent;
            border: 1px solid #2a2a3e;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .copy-code-btn:hover {
            background-color: #2a2a3e;
            color: #ffffff;
        }

        .copy-code-btn:active {
            transform: scale(0.98);
        }

        .code-block-wrapper pre {
            margin: 0;
            padding: 1rem;
            overflow-x: auto;
            background-color: transparent !important;
        }

        .code-block-wrapper code {
            font-family: 'JetBrains Mono', monospace !important;
            font-size: 0.875rem;
            line-height: 1.6;
        }
    </style>

    @yield('styles')

    {{-- Prevent unstyled HTML flash while Vite loads CSS --}}
    <style id="fouc-preventer">
        body { opacity: 0; }
    </style>
    <script>
        window.addEventListener('load', function() {
            var preventer = document.getElementById('fouc-preventer');
            if (preventer) {
                preventer.remove();
                document.body.style.opacity = '1';
                document.body.style.transition = 'opacity 0.2s ease-in-out';
            }
        });
        setTimeout(function() {
            var preventer = document.getElementById('fouc-preventer');
            if (preventer) {
                preventer.remove();
                document.body.style.opacity = '1';
            }
        }, 500);
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased">
    <div class="min-h-screen flex flex-col">
        @yield('content')
    </div>

    {{-- Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        // Initialize Lucide Icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>

    @yield('scripts')
</body>
</html>
