self.addEventListener("install", (event) => {
    event.waitUntil(
        caches.open("app-cache").then((cache) => {
            return cache.addAll([
                "/",
                "/index.php",
                "/manifest.json",
                "/sw.js",
                "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
            ]);
        })
    );
});

self.addEventListener("fetch", (event) => {
    event.respondWith(
        caches.match(event.request).then((response) => {
            return response || fetch(event.request);
        })
    );
});
