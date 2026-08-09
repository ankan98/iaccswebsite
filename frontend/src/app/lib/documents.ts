export type DocumentType = "announcements" | "notices" | "reports";

export type DocumentItem = {
  title: string;
  description?: string;
  url?: string | null;
  fileName?: string | null;
  createdAt?: string | null;
  updatedAt?: string | null;
};

export type DocumentsResponse = {
  success: boolean;
  type?: DocumentType;
  total?: number;
  limit?: number;
  offset?: number;
  items?: DocumentItem[];
  message?: string;
};

import { buildPhpUrl } from "@/app/lib/utils";

export function normalizeHostname(host?: string | null) {
  if (!host) return "";
  return host.split(":")[0]?.toLowerCase() ?? "";
}

export function getDocumentsBaseUrlByHost(hostname?: string | null) {
  return buildPhpUrl("documents_list.php", hostname);
}

export function buildDocumentsUrl(params: {
  type: DocumentType;
  limit?: number;
  offset?: number;
  baseUrl?: string;
  hostname?: string | null;
}) {
  const baseUrl =
    params.baseUrl ?? getDocumentsBaseUrlByHost(params.hostname);
  const search = new URLSearchParams({ type: params.type });
  if (typeof params.limit === "number") search.set("limit", String(params.limit));
  if (typeof params.offset === "number")
    search.set("offset", String(params.offset));
  return `${baseUrl}?${search.toString()}`;
}
