"use client";

import { useState, useEffect } from "react";
import { resolveAssetUrl, getPhpBaseUrl } from "@/app/lib/utils";

interface DynamicPageClientProps {
  initialPageData: any;
  slug: string;
  backendUrl?: string;
}

export default function DynamicPageClient({ initialPageData, slug }: DynamicPageClientProps) {
  const [pageData, setPageData] = useState<any>(initialPageData);

  useEffect(() => {
    let activeSlug = slug && slug !== "dynamic-page" ? slug : "";
    if (!activeSlug && typeof window !== "undefined") {
      activeSlug = window.location.pathname.replace(/^\/+|\/+$/g, "");
    }

    if (!activeSlug) return;

    const apiBase = getPhpBaseUrl();
    fetch(`${apiBase}/get_page.php?slug=${encodeURIComponent(activeSlug)}`)
      .then((res) => (res.ok ? res.json() : null))
      .then((data) => {
        if (data && !data.error) {
          setPageData(data);
        }
      })
      .catch((e) => console.error("Client fetch dynamic page data error:", e));
  }, [slug]);

  let heroConfig: any = null;
  if (pageData?.hero_json) {
    try {
      heroConfig = typeof pageData.hero_json === "string"
        ? JSON.parse(pageData.hero_json)
        : pageData.hero_json;
    } catch (e) {
      console.error("Failed to parse hero_json:", e);
    }
  }



  // Strictly follow CMS toggle button: hero_active === 1 / true / "1"
  const isHeroActive =
    heroConfig?.hero_active === 1 ||
    heroConfig?.hero_active === true ||
    heroConfig?.hero_active === "1";

  const heroBgImage = heroConfig?.hero_image ? resolveAssetUrl(heroConfig.hero_image) : "";

  // Strip duplicate heading if already present in content HTML
  let contentHtml = pageData?.content || "";
  if (contentHtml && pageData?.heading) {
    const escapedHeading = pageData.heading.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const headingRegex = new RegExp(`<h[1-6][^>]*>\\s*` + escapedHeading + `\\s*<\\/h[1-6]>`, 'gi');
    contentHtml = contentHtml.replace(headingRegex, '');
  }

  return (
    <div className="bg-white">
      {/* Conditional Layout: Hero Banner mode OR Standard Page Header mode */}
      {isHeroActive ? (
        <>
          {/* ── HERO BANNER SECTION ── */}
          <header
            className="relative w-full flex flex-col bg-cover bg-center py-20 md:py-24 lg:py-32 mt-20"
            style={heroBgImage ? { backgroundImage: `url(${heroBgImage})` } : { backgroundColor: "#1a4075" }}
          >
            <div className="absolute inset-0 bg-black/50"></div>
            <div className="mx-auto px-4 sm:px-6 md:px-12 lg:px-[110px] relative z-[1]">
              <div className="w-full flex flex-col gap-6 md:gap-8 max-w-[900px]">
                <div className="text-white">
                  <h1
                    className="font-bold text-white text-2xl sm:text-3xl md:text-4xl lg:text-[45px] leading-snug lg:leading-[64px]"
                    style={{ fontFamily: "'Playfair Display', serif", textShadow: "1px 4px 1px rgb(0,0,0)" }}
                  >
                    {heroConfig?.hero_title || pageData?.heading || pageData?.title}
                  </h1>
                  {heroConfig?.hero_subtitle && (
                    <p
                      className="mt-4 text-white/80 text-sm sm:text-base md:text-lg"
                      style={{ fontFamily: '"Times New Roman", Times, serif' }}
                    >
                      {heroConfig.hero_subtitle}
                    </p>
                  )}
                </div>

                {heroConfig?.hero_description && (
                  <p
                    className="text-sm sm:text-base md:text-lg text-white/90 leading-relaxed"
                    style={{ fontFamily: '"Times New Roman", Times, serif' }}
                  >
                    {heroConfig.hero_description}
                  </p>
                )}

                {heroConfig?.hero_btn_text && (
                  <div className="flex flex-wrap gap-4">
                    <a
                      href={heroConfig.hero_btn_link || "#"}
                      className="inline-block px-6 md:px-8 py-2 md:py-3 text-gray-900 rounded-full border-2 border-solid border-black hover:opacity-90 transition text-sm md:text-base font-bold bg-[#38b6ff]"
                    >
                      {heroConfig.hero_btn_text}
                    </a>
                  </div>
                )}
              </div>
            </div>
          </header>

          {/* Body Content below Hero */}
          <main className="w-full bg-white flex flex-col py-12 md:py-16 xl:px-[110px] md:px-20 px-5">
            {contentHtml && (
              <div
                className="tinymce-content prose max-w-none text-gray-800 leading-relaxed"
                dangerouslySetInnerHTML={{ __html: contentHtml }}
              />
            )}
          </main>
        </>
      ) : (
        <>
          {/* ── STANDARD DYNAMIC PAGE HEADER ── */}
          <main className="w-full bg-white flex flex-col py-16 xl:px-[110px] md:px-20 px-5 mt-[40px]">
            <h1
              className="w-full font-bold text-gray-900 xl:text-[48px] md:text-3xl text-2xl !leading-tight mb-4"
              style={{ fontFamily: "'Playfair Display', serif" }}
            >
              {pageData?.heading || pageData?.title}
            </h1>

            {pageData?.subheading && (
              <p className="text-gray-700 max-w-3xl mb-6 text-lg">
                {pageData.subheading}
              </p>
            )}

            {contentHtml && (
              <div
                className="tinymce-content prose max-w-none text-gray-800 leading-relaxed"
                dangerouslySetInnerHTML={{ __html: contentHtml }}
              />
            )}
          </main>
        </>
      )}

      {/* Custom CSS Rules rendered right before container closes */}
      {pageData?.custom_css && (
        <style dangerouslySetInnerHTML={{ __html: pageData.custom_css }} />
      )}
    </div>
  );
}
