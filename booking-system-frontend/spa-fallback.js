// This is a simplified version of @vitejs/plugin-legacy
// It forces index.html to be served for any route without a file extension
export default function() {
  return {
    name: 'spa-fallback',
    configureServer(server) {
      server.middlewares.use((req, res, next) => {
        const url = req.url;
        
        // Allow /public/{username} routes to be handled by Vue router
        // But exclude specific API endpoints like /public/branding, /public/user, etc.
        const isPublicBookingRoute = url.match(/^\/public\/[^\/]+$/) && !url.includes('.');
        
        // Ignore Vite's internal paths and API routes, but allow public booking routes
        if ((!url.includes('.') && 
            !url.startsWith('/api/') &&
            !url.startsWith('/@') && 
            !url.startsWith('/__') && 
            !url.startsWith('/node_modules/')) || isPublicBookingRoute) {
          
          // Don't redirect specific public API endpoints
          if (!url.match(/^\/public\/(availability|booking|user\/|branding\/)/)) {
            console.log(`[SPA Fallback] Redirecting ${url} to /index.html`);
            req.url = '/index.html';
          }
        }
        
        next();
      });
    }
  };
}