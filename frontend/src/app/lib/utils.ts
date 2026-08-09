export function getPhpBaseUrl(hostname?: string | null): string {
  // 1. Check if configured in environment variables (works both in server/client if prefixed with NEXT_PUBLIC_)
  if (process.env.NEXT_PUBLIC_PHP_BACKEND_URL) {
    // Remove trailing slash if present
    return process.env.NEXT_PUBLIC_PHP_BACKEND_URL.replace(/\/$/, "");
  }

  // 2. Resolve hostname: check provided parameter first, then fallback to window.location
  const host = hostname || (typeof window !== "undefined" ? window.location.hostname : "");
  if (host === "localhost" || host === "127.0.0.1") {
    return "http://localhost:8000";
  }
  if (host.endsWith("agcinfosystem.com")) {
    return "https://iaccs.org.in";
  }

  // 3. Production fallback
  return "https://iaccs.org.in";
}

export function buildPhpUrl(path: string, hostname?: string | null): string {
  const base = getPhpBaseUrl(hostname);
  const normalizedPath = path.startsWith("/") ? path : `/${path}`;
  return `${base}${normalizedPath}`;
}

export function resolveAssetUrl(url?: string | null, fallback: string = ""): string {
  if (!url) return fallback;
  const trimmed = url.trim();
  if (!trimmed) return fallback;

  // 1. Full absolute URLs (http:// or https://)
  if (trimmed.startsWith("http://") || trimmed.startsWith("https://")) {
    return trimmed;
  }

  // 2. Base64 data URLs
  if (trimmed.startsWith("data:")) {
    return trimmed;
  }

  // Clean leading slash
  const cleanPath = trimmed.replace(/^\/+/, "");

  // 3. CMS User Uploads (starts with 'uploads/' or contains '/uploads/')
  if (cleanPath.startsWith("uploads/") || cleanPath.startsWith("cms/uploads/")) {
    const uploadPath = cleanPath.replace(/^cms\//, ""); // strip accidental 'cms/' prefix

    // If running in browser:
    if (typeof window !== "undefined") {
      const host = window.location.hostname;
      const port = window.location.port;

      if (host === "localhost" || host === "127.0.0.1") {
        if (port === "8000") {
          // Serving directly from PHP/Static build on port 8000
          return `/${uploadPath}`;
        }
        // Dev server on port 3000
        return `http://localhost:8000/${uploadPath}`;
      }
    }

    // Server-side or production fallback
    const backendUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || "https://iaccs.org.in";
    return `${backendUrl.replace(/\/$/, "")}/${uploadPath}`;
  }

  // 4. Local static frontend assets (e.g. assets/images/..., iaccslogo.png, etc.)
  if (cleanPath.startsWith("assets/") || cleanPath.startsWith("images/")) {
    return `/${cleanPath}`;
  }

  return `/${cleanPath}`;
}
