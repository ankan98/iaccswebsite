export function getPhpBaseUrl(hostname?: string | null): string {
  // 1. In browser environment: dynamically use current window.location.origin
  if (typeof window !== "undefined") {
    return window.location.origin;
  }

  // 2. Read from environment variables (.env / .env.local)
  const envUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || process.env.NEXT_PUBLIC_BASE_URL;
  if (envUrl) {
    return envUrl.replace(/\/$/, "");
  }

  // 3. Server-side / Build default fallback
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
    return `/${uploadPath}`;
  }

  // 4. Local static frontend assets
  if (cleanPath.startsWith("assets/") || cleanPath.startsWith("images/")) {
    return `/${cleanPath}`;
  }

  return `/${cleanPath}`;
}
