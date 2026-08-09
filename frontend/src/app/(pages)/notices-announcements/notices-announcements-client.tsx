"use client";

import Link from "next/link";
import { useState, useEffect } from "react";
import { getPhpBaseUrl } from "@/app/lib/utils";
import DocumentsList from "@/app/components/documents-list";
import HashScroll from "@/app/components/hash-scroll";

interface NoticesAnnouncementsClientProps {
  initialPageData: any;
  backendUrl?: string;
}

export default function NoticesAnnouncementsClient({ initialPageData }: NoticesAnnouncementsClientProps) {
  const [pageData, setPageData] = useState<any>(initialPageData);

  useEffect(() => {
    const apiBase = getPhpBaseUrl();
    fetch(`${apiBase}/get_page.php?slug=notices-announcements`)
      .then((res) => (res.ok ? res.json() : null))
      .then((data) => {
        if (data && !data.error) {
          setPageData(data);
        }
      })
      .catch((e) => console.error("Client fetch notices page data error:", e));
  }, []);

  return (
    <div className="bg-white">
      {pageData?.custom_css && (
        <style dangerouslySetInnerHTML={{ __html: pageData.custom_css }} />
      )}
      <HashScroll />
      <section className="w-full bg-white flex flex-col gap-10 py-16 xl:px-[110px] md:px-20 px-5 mt-[40px]">
        <div className="w-full">
          <h1
            className="w-full font-bold text-gray-900 xl:text-[48px] md:text-3xl text-2xl !leading-tight mb-4"
            style={{ fontFamily: "Georgia, serif" }}
          >
            {pageData?.title || "Notices & Announcements"}
          </h1>
          
          {pageData?.heading && pageData.heading !== pageData.title && (
            <h2 className="text-xl font-semibold text-gray-800 mb-1">
              {pageData.heading}
            </h2>
          )}

          <p className="text-gray-700 max-w-3xl">
            {pageData?.subheading || "Stay updated with the latest announcements, notices, and reports from ACCS."}
          </p>

          {pageData?.btn_text && pageData?.btn_link && (
            <div className="my-3">
              <a href={pageData.btn_link} className="inline-block px-6 py-2.5 bg-[#38b6ff] text-black font-bold rounded-full border border-black hover:opacity-90 transition text-sm">
                {pageData.btn_text}
              </a>
            </div>
          )}

          {pageData?.content && (
            <div className="mt-4 prose max-w-4xl" dangerouslySetInnerHTML={{ __html: pageData.content }} />
          )}
        </div>

        <div id="announcements">
          <div className="text-center">
            <Link
              href="/notices-announcements#announcements"
              className="bg-green-500 text-white w-[300px] inline-flex items-center gap-3 px-6 py-3 rounded-full mb-6  justify-center h-[52px]"
            >
              <img src="/assets/images/img24.png" alt="Announcements Icon" width={28} />
              <h3 className="text-lg md:text-xl font-semibold">
                Announcements
              </h3>
            </Link>
            <DocumentsList
              type="announcements"
              limit={0}
              className="max-w-4xl mx-auto"
              listClassName="space-y-3 text-base md:text-lg"
            />
          </div>
        </div>

        <div id="notices">
          <div className="text-center">
            <Link
              href="/notices-announcements#notices"
              className="bg-green-500 text-white w-[300px] inline-flex items-center gap-3 px-6 py-3 rounded-full mb-6  justify-center h-[52px]"
            >
              <img src="/assets/images/img57.png" alt="Notices Icon" width={28} />
              <h3 className="text-lg md:text-xl font-semibold">Notices</h3>
            </Link>
            <DocumentsList
              type="notices"
              limit={0}
              className="max-w-4xl mx-auto"
              listClassName="space-y-3 text-base md:text-lg"
            />
          </div>
        </div>

        <div id="reports">
          <div className="text-center">
            <Link
              href="/notices-announcements#reports"
              className="bg-green-500 text-white w-[300px] inline-flex items-center gap-3 px-6 py-3 rounded-full mb-6  justify-center h-[52px]"
            >
              <img src="/assets/images/reports.png" alt="Reports Icon" width={28} />
              <h3 className="text-lg md:text-xl font-semibold">
                Reports
              </h3>
            </Link>
            <DocumentsList
              type="reports"
              limit={0}
              className="max-w-4xl mx-auto"
              listClassName="space-y-3 text-base md:text-lg"
            />
          </div>
        </div>
      </section>
    </div>
  );
}
