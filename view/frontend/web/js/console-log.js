(function () {
    "use strict";

    if (window.__ConsoleLogInitialized) {
        return;
    }
    window.__ConsoleLogInitialized = true;

    var allowed = true;

    if (!allowed) {
        return;
    }

    /**
     * @type {Array<{level: string, time: number, url: string, data: Array}>}
     */
    var buffer = [];

    /**
     * Intercept console methods.
     * @type {string[]}
     */
    var levels = ["log", "warn", "error", "info", "debug"];

    levels.forEach(function (level) {
        var original = console[level];

        console[level] = function () {
            var args = Array.prototype.slice.call(arguments);

            buffer.push({
                level: level,
                time: Date.now(),
                url: window.location.href,
                data: args,
            });

            if (original) {
                original.apply(console, args);
            }
        };
    });

    /**
     * Flush the buffer to the server via sendBeacon.
     */
    var flusher = function () {
        if (!buffer.length) {
            return;
        }

        var payload = JSON.stringify(buffer);

        var sent = false;

        try {
            fetch("/consolelog/logger/save", {
                method: "POST",
                body: payload,
                keepalive: true,
                headers: { "Content-Type": "application/json" },
            });
        } catch (e) {
            console.error("[ConsoleLog] fetch fallback error:", e);
        }

        console.log(
            "[ConsoleLog] Flushed",
            buffer.length,
            "logs | beacon:",
            sent,
        );
        buffer = [];
    };

    window.flushConsoleLogs = flusher;

    setInterval(flusher, 30000);
})();
