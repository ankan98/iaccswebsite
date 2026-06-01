import Link from "next/link";
import type { DocumentItem } from "@/app/lib/documents";

export default function DocumentsListStatic(props: {
  items: DocumentItem[];
  showMoreHref?: string;
  showDescription?: boolean;
  className?: string;
  listClassName?: string;
  emptyMessage?: string;
}) {
  const items = Array.isArray(props.items) ? props.items : [];
  const showMoreHref = props.showMoreHref;
  const showMore = Boolean(showMoreHref) && items.length > 0;

  return (
    <div className={props.className}>
      {items.length === 0 ? (
        <div className="text-center text-gray-700">
          {props.emptyMessage ?? "No Records found"}
        </div>
      ) : (
        <ul className={props.listClassName ?? "space-y-3 text-base md:text-lg"}>
          {items.map((item, idx) => (
            <li
              key={`${item.url ?? item.fileName ?? item.title}-${idx}`}
              className="flex items-center justify-between gap-3"
            >
              <div className="text-left break-words">
                <div className="text-gray-800">&gt; {item.title}</div>
                {props.showDescription && item.description?.trim() ? (
                  <div className="mt-1 text-sm text-gray-600">
                    {item.description}
                  </div>
                ) : null}
              </div>

              {item.url ? (
                <a
                  href={item.url}
                  download
                  className="shrink-0 px-4 py-1 rounded-full bg-[#38b6ff] border border-black text-sm font-semibold hover:opacity-90 transition"
                >
                  Download
                </a>
              ) : null}
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
