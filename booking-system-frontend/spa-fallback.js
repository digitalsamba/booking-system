// This is a simplified version of @vitejs/plugin-legacy
// It forces index.html to be served for any route without a file extension
export default function() {
  return {
    name: 'spa-fallback',
    configureServer(server) {
      // Middleware to serve index.html for paths that don't include a file extension
      return () => {
        server.middlewares.use((req, res, next) => {
          const url = req.url;
          
          // If the URL has no file extension, serve index.html
          if (!url.includes('.') && !url.startsWith('/api/')) {
            console.log(`[SPA Fallback] Redirecting ${url} to /index.html`);
            req.url = '/index.html';
          }
          
          next();
        });
      };
    }
  };
}