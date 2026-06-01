"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import {
  buildDocumentsUrl,
  type DocumentItem,
  type DocumentType,
  type DocumentsResponse,
} from "@/app/lib/documents";

export default function DocumentsList(props: {
  type: DocumentType;
  limit?: number;
  showMoreHref?: string;
  showDescription?: boolean;
  className?: string;
  listClassName?: string;
}) {
  const limit = props.limit ?? 10;
  const [items, setItems] = useState<DocumentItem[] | null>(null);
  const [hasError, setHasError] = useState(false);

  const url = useMemo(() => {
    if (typeof window === "undefined") return "";
    return buildDocumentsUrl({
      type: props.type,
      limit,
      hostname: window.location.hostname,
    });
  }, [props.type, limit]);

  useEffect(() => {
    if (!url) return;

    let cancelled = false;
    (async () => {
      try {
        setHasError(false);
        const res = await fetch(url, { method: "GET", cache: "no-store" });
        const json = (await res.json()) as DocumentsResponse;
        if (!res.ok || !json?.success) throw new Error("Bad response");
        if (cancelled) return;
        setItems(Array.isArray(json.items) ? json.items : []);
      } catch {
        if (!cancelled) setHasError(true);
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [url]);

  const showMoreHref = props.showMoreHref;
  const showMore = Boolean(showMoreHref) && (items?.length ?? 0) > 0;

  if (hasError) {
    return (
      <div className={props.className}>
        <div className="text-center text-gray-700">No Records found</div>
      </div>
    );
  }

  if (items === null) {
    return (
      <div className={props.className}>
        <div className="text-center text-gray-500">Loading...</div>
      </div>
    );
  }

  const nowMs = Date.now();
  const tenDaysMs = 10 * 24 * 60 * 60 * 1000;
  const isNewItem = (dateValue?: string | null) => {
    if (!dateValue) return false;
    const parsed = Date.parse(dateValue);
    if (Number.isNaN(parsed)) return false;
    return nowMs - parsed <= tenDaysMs;
  };

  return (
    <div className={props.className}>
      {items.length === 0 ? (
        <div className="text-center text-gray-700">No Records found</div>
      ) : (
        <ul className={props.listClassName ?? "space-y-1 text-base md:text-lg"}>
          {items.map((item, idx) => (
            <li
              key={`${item.url ?? item.fileName ?? item.title}-${idx}`}
              className="rounded-xl px-4 py-1 shadow-sm transition hover:shadow text-[14px]"
            >
              {item.url ? (
                <a
                  href={item.url}
                  download
                  className="flex items-center justify-between gap-3 w-full"
                >
                  <div className="text-left break-words">
                    <div className="text-gray-800">&gt; {item.title}</div>
                    {props.showDescription && item.description?.trim() ? (
                      <div className="mt-1 text-sm text-gray-600">
                        {item.description}
                      </div>
                    ) : null}
                  </div>

                  <div className="shrink-0 flex items-center gap-2">
                    {isNewItem(item.createdAt ?? item.updatedAt) ? (
                      <span className="inline-flex items-center text-xs font-extrabold uppercase tracking-wide bg-gradient-to-r from-pink-500 via-amber-400 to-emerald-400 bg-clip-text text-transparent animate-pulse">
                        New
                      </span>
                    ) : null}
                    <span className="text-sm font-semibold text-blue-700 underline underline-offset-2 hover:text-blue-900">
                      Download
                    </span>
                  </div>
                </a>
              ) : (
                <div className="flex items-center justify-between gap-3 w-full">
                  <div className="text-left break-words">
                    <div className="text-gray-800">&gt; {item.title}</div>
                    {props.showDescription && item.description?.trim() ? (
                      <div className="mt-1 text-sm text-gray-600">
                        {item.description}
                      </div>
                    ) : null}
                  </div>

                  <div className="shrink-0 flex items-center gap-2">
                    {isNewItem(item.createdAt ?? item.updatedAt) ? (
                      <span className="inline-flex items-center text-xs font-extrabold uppercase tracking-wide bg-gradient-to-r from-pink-500 via-amber-400 to-emerald-400 bg-clip-text text-transparent animate-pulse">
                        New
                      </span>
                    ) : null}
                  </div>
                </div>
              )}
            </li>
          ))}
        </ul>
      )}

      {showMore && showMoreHref && (
        <div className="mt-4 text-center">
          <Link href={showMoreHref} className="text-blue-600 underline">
            Show more
          </Link>
        </div>
      )}
    </div>
  );
}
