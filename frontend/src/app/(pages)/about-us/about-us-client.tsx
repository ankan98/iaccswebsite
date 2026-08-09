"use client";

import { useState, useEffect } from "react";
import { resolveAssetUrl, getPhpBaseUrl } from "@/app/lib/utils";
import MembersCarousel from "@/app/components/members-carousel";

interface AboutUsClientProps {
  initialPageData: any;
  backendUrl?: string;
}

export default function AboutUsClient({ initialPageData }: AboutUsClientProps) {
  const [pageData, setPageData] = useState<any>(initialPageData);

  useEffect(() => {
    const apiBase = getPhpBaseUrl();
    fetch(`${apiBase}/get_page.php?slug=about-us`)
      .then((res) => (res.ok ? res.json() : null))
      .then((data) => {
        if (data && !data.error) {
          setPageData(data);
        }
      })
      .catch((e) => console.error("Client fetch about-us page data error:", e));
  }, []);

  let aboutConfig: any = null;
  if (pageData?.about_json) {
    try {
      aboutConfig =
        typeof pageData.about_json === "string"
          ? JSON.parse(pageData.about_json)
          : pageData.about_json;
    } catch (e) {
      console.error("Failed to parse about_json:", e);
    }
  }

  // Members list — fallback to defaults if DB is empty
  const members = (aboutConfig?.members?.length
    ? aboutConfig.members
    : [
        {
          name: "BAPAN SARKAR",
          role: "President",
          qualification: "M.sc CCST",
          image: "/assets/images/bapan-sarkar.jpg",
        },
        {
          name: "ATRI BANERJEE",
          role: "General Secretary",
          qualification: "B.sc CCT",
          image: "/assets/images/atri-banerjee.jpg",
        },
      ]
  ).map((m: any) => ({
    ...m,
    image: resolveAssetUrl(m.image, "/assets/images/bapan-sarkar.jpg"),
  }));

  const membersTitle      = aboutConfig?.members_title           || "Our Governing Members";
  const membersAutoplay   = aboutConfig?.members_autoplay        ?? true;
  const membersAutoplayMs = aboutConfig?.members_autoplay_speed  ?? 4000;

  return (
    <div className="bg-white mt-[40px]">
      {/* ── Page Header & Body Content ── */}
      <section className="w-full bg-white flex flex-col py-16 xl:px-[110px] md:px-20 px-5">
        <h1
          className="w-full font-bold text-gray-900 xl:text-[48px] md:text-3xl text-2xl !leading-tight mb-[20px]"
          style={{ fontFamily: "Georgia, serif" }}
        >
          {pageData?.title || "About Us"}
        </h1>

        {pageData?.heading && (
          <h2
            className="font-bold xl:text-[30px] md:text-3xl text-2xl !leading-tight mb-8"
            style={{ color: "#1a4075" }}
          >
            {pageData.heading}
          </h2>
        )}

        <div className="w-full text-gray-700 leading-relaxed space-y-6">
          {pageData?.content ? (
            <div className="tinymce-content" dangerouslySetInnerHTML={{ __html: pageData.content }} />
          ) : (
            <>
              <p>
                The Association for Critical Care Sciences (ACCS) was founded to represent, strengthen, and advance the discipline of Critical Care Sciences in India. As an association, we work to foster collaboration between healthcare delivery systems, universities, government authorities, regulatory bodies, and industry stakeholders.
              </p>
              <p>
                Our core goal is to elevate the standing of Critical Care Science as a distinct, specialized, and indispensable allied health profession. We provide active representation for our members, advocating for proper recognition, standardization of clinical roles, and fair career progression pathways.
              </p>

              {/* Bullet / Grid feature highlights */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6 my-8 pt-4">
                <div className="border border-gray-200 rounded-2xl p-6 bg-slate-50/50 hover:border-blue-300 transition-colors">
                  <div className="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-lg mb-3">
                    01
                  </div>
                  <h3 className="text-xl font-bold text-gray-900 mb-2">Professional Advocacy</h3>
                  <p className="text-sm text-gray-600">
                    Engaging with regulatory agencies and health ministries to advocate for official policy recognition of Critical Care Science practitioners across public and private health setups.
                  </p>
                </div>

                <div className="border border-gray-200 rounded-2xl p-6 bg-slate-50/50 hover:border-blue-300 transition-colors">
                  <div className="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-lg mb-3">
                    02
                  </div>
                  <h3 className="text-xl font-bold text-gray-900 mb-2">Academic &amp; Skill Empowerment</h3>
                  <p className="text-sm text-gray-600">
                    Organizing specialized hands-on workshops, webinars, clinical CME programs, and distributing high-yield study resources for Critical Care Technologists.
                  </p>
                </div>

                <div className="border border-gray-200 rounded-2xl p-6 bg-slate-50/50 hover:border-blue-300 transition-colors">
                  <div className="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-lg mb-3">
                    03
                  </div>
                  <h3 className="text-xl font-bold text-gray-900 mb-2">Quality &amp; Safety Standards</h3>
                  <p className="text-sm text-gray-600">
                    Setting ethical, operational, and clinical guidelines to optimize patient outcomes in Intensive Care Units (ICUs) and emergency care departments.
                  </p>
                </div>

                <div className="border border-gray-200 rounded-2xl p-6 bg-slate-50/50 hover:border-blue-300 transition-colors">
                  <div className="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-lg mb-3">
                    04
                  </div>
                  <h3 className="text-xl font-bold text-gray-900 mb-2">Nationwide Network</h3>
                  <p className="text-sm text-gray-600">
                    Building a unified community of ICU technologists, educators, and students to facilitate knowledge sharing and professional career growth.
                  </p>
                </div>
              </div>
            </>
          )}
        </div>
      </section>

      {/* ── Governing Members Carousel Section ── */}
      <section className="w-full bg-slate-50 py-16">
        <div className="relative group px-4 sm:px-6 lg:px-[110px]">
          <h2 className="text-center text-2xl md:text-3xl lg:text-4xl font-semibold text-gray-800 mb-10">
            {membersTitle}
          </h2>

          <MembersCarousel
            members={members}
            autoplay={membersAutoplay}
            autoplaySpeed={membersAutoplayMs}
          />
        </div>

        {/* Button */}
        <div className="text-center mt-10 px-4">
          <a
            href={aboutConfig?.members_btn_link || "/membership"}
            className="inline-flex items-center justify-center font-bold px-8 py-3 rounded-full border-2 border-black transition-all hover:scale-[1.01] active:scale-[0.99] hover:opacity-90 text-sm md:text-base shadow-md"
            style={{
              backgroundColor: aboutConfig?.members_btn_bg_color || "#38b6ff",
              color: aboutConfig?.members_btn_text_color || "#000000",
            }}
          >
            {aboutConfig?.members_btn_text || "View Full list"}
          </a>
        </div>

        {/* Limitation Notice */}
        {aboutConfig?.members_notice && (
          <div className="w-full px-4 sm:px-6 lg:px-[110px] mb-4 mt-8">
            <div className="mx-auto border-2 border-solid border-gray-500 rounded-xl p-4 text-red-500 text-center text-sm md:text-base">
              {aboutConfig.members_notice}
            </div>
          </div>
        )}
      </section>

      {/* Custom CSS Rules rendered right before container closes */}
      {pageData?.custom_css && (
        <style dangerouslySetInnerHTML={{ __html: pageData.custom_css }} />
      )}
    </div>
  );
}
