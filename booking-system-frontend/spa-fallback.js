// This is a simplified version of @vitejs/plugin-legacy
// It forces index.html to be served for any route without a file extension
export default function() {
  return {
    name: 'spa-fallback',
    configureServer(server) {
      server.middlewares.use((req, res, next) => {
        const url = req.url;
        
        // Ignore Vite's internal paths and API routes
        if (!url.includes('.') && 
            !url.startsWith('/api/') && 
            !url.startsWith('/public/') &&
            !url.startsWith('/@') && 
            !url.startsWith('/__') && 
            !url.startsWith('/node_modules/')) {
          console.log(`[SPA Fallback] Redirecting ${url} to /index.html`);
          req.url = '/index.html';
        }
        
        next();
      });
    }
  };
}