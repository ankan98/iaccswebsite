"use client";

import { useEffect, useMemo, useState } from "react";

type Counts = {
  student: number;
  premium: number;
};

function getCountsUrl() {
  const host = window.location.hostname;
  if (host === "localhost" || host === "127.0.0.1") {
    return "http://localhost/iaccs/membership_counts.php";
  }
  if (host.endsWith("agcinfosystem.com")) {
    return "https://iaccs.org.in/membership_counts.php";
  }
  return "/membership_counts.php";
}

export default function MemberStats() {
  const [counts, setCounts] = useState<Counts | null>(null);
  const [hasError, setHasError] = useState(false);

  const url = useMemo(() => {
    if (typeof window === "undefined") return "";
    return getCountsUrl();
  }, []);

  useEffect(() => {
    if (!url) return;

    let cancelled = false;
    (async () => {
      try {
        setHasError(false);
        const res = await fetch(url, { method: "GET", cache: "no-store" });
        const json = await res.json();
        if (!res.ok || !json?.success) throw new Error("Bad response");
        if (!cancelled) {
          setCounts({
            student: Number(json.counts?.student ?? 0),
            premium: Number(json.counts?.premium ?? 0),
          });
        }
      } catch {
        if (!cancelled) setHasError(true);
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [url]);

  const studentText = counts ? `${counts.student}+ Student Members` : "… Student Members";
  const premiumText = counts ? `${counts.premium}+ Professional Members` : "… Professional Members";

  return (
    <div className="mt-4 border-2 border-solid border-gray-400 rounded-xl grid grid-cols-1 md:grid-cols-2 text-center mb-4">
      <div className="py-6 border-b-2 md:border-b-0 md:border-r-2 border-solid border-gray-400">
        <h3 className="text-xl md:text-2xl font-semibold text-green-600">
          {studentText}
        </h3>
      </div>

      <div className="py-6">
        <h3 className="text-xl md:text-2xl font-semibold text-green-600">
          {premiumText}
        </h3>
      </div>

      {hasError && (
        <div className="md:col-span-2 pb-4 text-sm text-gray-500">
          Member counts temporarily unavailable.
        </div>
      )}
    </div>
  );
}

