"use client";

import Link from "next/link";
import { useState, useEffect } from "react";
import { resolveAssetUrl, getPhpBaseUrl } from "@/app/lib/utils";
import MemberStats from "./components/member-stats";
import DocumentsList from "./components/documents-list";
import HospitalCarousel from "./components/hospital-carousel";
import MembersCarousel from "./components/members-carousel";

interface HomeClientProps {
  initialPageData: any;
  backendUrl: string;
}

export default function HomeClient({ initialPageData, backendUrl }: HomeClientProps) {
  const [pageData, setPageData] = useState<any>(initialPageData);

  const parseConfig = (data: any) => {
    if (data && data.home_json) {
      try {
        return typeof data.home_json === "string"
          ? JSON.parse(data.home_json)
          : data.home_json;
      } catch (e) {
        console.error("Failed to parse home_json", e);
      }
    }
    return null;
  };

  const [config, setConfig] = useState<any>(parseConfig(initialPageData));

  // Live client fetch from database
  useEffect(() => {
    const apiBase = getPhpBaseUrl();
    fetch(`${apiBase}/get_page.php?slug=home`)
      .then((res) => (res.ok ? res.json() : null))
      .then((data) => {
        if (data && !data.error) {
          setPageData(data);
          setConfig(parseConfig(data));
        }
      })
      .catch((e) => console.error("Client fetch home data error:", e));
  }, []);

  const heroBgImage = resolveAssetUrl(config?.hero_bg_image, "/assets/images/img189.jpg");

  // Resolve members photos
  const members = (config?.members || [
    {
      name: "BAPAN SARKAR",
      role: "President",
      qualification: "M.sc CCST",
      image: "/assets/images/bapan-sarkar.jpg"
    },
    {
      name: "ATRI BANERJEE",
      role: "General Secretary",
      qualification: "B.sc CCT",
      image: "/assets/images/atri-banerjee.jpg"
    }
  ]).map((m: any) => ({
    ...m,
    image: resolveAssetUrl(m.image)
  }));

  // Resolve slider images
  const carouselImages = (config?.carousel_images || [
    "/assets/images/img297.jpg",
    "/assets/images/img300.jpg",
    "/assets/images/img303.jpg"
  ]).map((img: string) => resolveAssetUrl(img));

  // Resolve cards
  const cards = (config?.cards || [
    {
      title: "Advocacy for Recognition",
      description: "Working toward the official recognition of Critical Care Technology/Science under national healthcare frameworks. We collaborate with policymakers, institutions, and stakeholders to secure professional identity and rights",
      image: "/assets/images/img324.jpg"
    },
    {
      title: "Training & Skill Development",
      description: "Helping students and professionals enhance their knowledge and hands-on ICU skills through structured programs and learning opportunities.",
      image: "/assets/images/img327.jpg"
    },
    {
      title: "Academic Support & Study Resources",
      description: "Providing structured learning materials, mentorship, and access to essential educational resources for students and practicing professionals in critical care domains.",
      image: "/assets/images/img330.jpg"
    }
  ]).map((c: any) => ({
    ...c,
    image: resolveAssetUrl(c.image)
  }));

  return (
    <div className="bg-white">
      {/* <!-- Header Section --> */}
      <header
        className="relative w-full flex flex-col bg-cover bg-center py-20 md:py-24 lg:py-32 mt-20"
        style={{ backgroundImage: `url(${heroBgImage})` }}
      >
        <div className="absolute inset-0 bg-black/50"></div>
        <div className="mx-auto px-4 sm:px-6 md:px-12 lg:px-[110px] relative z-[1]">
          <div className="w-full flex flex-col gap-6 md:gap-8 max-w-[900px]">
            <div className="text-white">
              <h1
                className="font-bold text-white text-2xl sm:text-3xl md:text-4xl lg:text-[45px] leading-snug lg:leading-[64px]"
                style={{ fontFamily: "'Playfair Display', serif", textShadow: "1px 4px 1px rgb(0,0,0)" }}
              >
                {pageData?.heading || "Welcome to ACCS The Association for Critical Care Sciences"}
              </h1>
              <p
                className="mt-4 text-white/80 text-sm sm:text-base md:text-lg"
                style={{ fontFamily: '"Times New Roman", Times, serif' }}
              >
                {pageData?.subheading || "RECOGNITION . STANDARDS . EXCELLENCE ."}
              </p>
            </div>
            {pageData?.content ? (
              <div
                className="text-sm sm:text-base md:text-lg text-white/90 leading-relaxed"
                style={{ fontFamily: '"Times New Roman", Times, serif' }}
                dangerouslySetInnerHTML={{ __html: pageData.content }}
              />
            ) : (
              <p
                className="text-sm sm:text-base md:text-lg text-white/90 leading-relaxed"
                style={{ fontFamily: '"Times New Roman", Times, serif' }}
              >
                ACCS is dedicated to advancing clinical excellence, promoting education, and strengthening the future workforce in Critical Care Science. Together, we work for recognition, standardization, and growth of our profession.
              </p>
            )}
            <div className="flex flex-wrap gap-4">
              <a
                href={pageData?.btn_link || "/membership"}
                className="inline-block px-6 md:px-8 py-2 md:py-3 text-gray-900 rounded-full border-2 border-solid border-black hover:opacity-90 transition text-sm md:text-base"
                style={{
                  backgroundColor: config?.hero_btn_bg_color || "#38b6ff",
                  fontFamily: '"Times New Roman", Times, serif',
                  fontWeight: "bold",
                  color: config?.hero_btn_text_color || "#000000",
                }}
              >
                {pageData?.btn_text || "JOIN US TODAY"}
              </a>
            </div>
          </div>
        </div>
      </header>

      {/* <!-- Vision & Mission Section --> */}
      <section className="px-4 sm:px-6 lg:px-[110px] py-12 md:py-16">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
          {/* Vision Card */}
          <div className="border-2 border-solid border-gray-500 rounded-2xl bg-white overflow-hidden">
            <img 
              src={resolveAssetUrl(config?.vision_image, "/assets/images/img222.jpg")} 
              alt={config?.vision_title || "Vision"} 
              className="w-full h-48 sm:h-56 md:h-64 object-cover"
            />
            <div className="p-5 md:p-6 text-center">
              <h2 className="inline-block px-5 md:px-6 py-2 text-xl md:text-2xl lg:text-3xl font-bold border-2 border-solid border-black rounded-full font-serif">
                {config?.vision_title || "VISION"}
              </h2>
              <p className="mt-4 text-gray-700 leading-relaxed text-sm md:text-base text-justify">
                {config?.vision_text || "The Association for Critical Care Sciences (ACCS) is a community-led initiative formed to represent, support, and advance the field of Critical Care Technology/Science in India. We work towards unifying students, graduates, educators, and professionals to strengthen recognition, create academic opportunities, and uphold high standards in clinical practice."}
              </p>
            </div>
          </div>

          {/* Mission Card */}
          <div className="border-2 border-solid border-gray-500 rounded-2xl bg-white overflow-hidden">
            <img 
              src={resolveAssetUrl(config?.mission_image, "/assets/images/img227.jpg")} 
              alt={config?.mission_title || "Mission"} 
              className="w-full h-48 sm:h-56 md:h-64 object-cover"
            />
            <div className="p-5 md:p-6 text-center">
              <h2 className="inline-block px-5 md:px-6 py-2 text-xl md:text-2xl lg:text-3xl font-bold border-2 border-solid border-black rounded-full font-serif">
                {config?.mission_title || "MISSION"}
              </h2>
              <p className="mt-4 text-gray-700 leading-relaxed text-sm md:text-base text-justify">
                {config?.mission_text || "To empower Critical Care Technology professionals through education, advocacy, collaboration, and skill development, ensuring excellence in patient care across Intensive Care settings. A future where Critical Care Technology/Science is nationally recognized and valued as an essential healthcare specialty supported by strong academic pathways, ethical practice, and professional dignity."}
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Governing Members */}
      <section>
        <div className="relative group px-4 sm:px-6 lg:px-[110px]">
          {/* Title */}
          <h2 className="text-center text-2xl md:text-3xl lg:text-4xl font-semibold text-gray-800 mb-10">
            {config?.members_title || "Our Governing Members"}
          </h2>

          {/* Members Carousel */}
          <MembersCarousel 
            members={members} 
            autoplay={config?.members_autoplay ?? true}
            autoplaySpeed={config?.members_autoplay_speed ?? 4000}
          />
        </div>

        {/* Button */}
        <div className="text-center mt-10 px-4">
          <Link
            href={config?.members_btn_link || "/about-us"}
            className="inline-flex items-center justify-center font-bold px-8 py-3 rounded-full border-2 border-black transition-all hover:scale-[1.01] active:scale-[0.99] hover:opacity-90 text-sm md:text-base shadow-md"
            style={{
              backgroundColor: config?.members_btn_bg_color || "#38b6ff",
              color: config?.members_btn_text_color || "#000000",
            }}
          >
            {config?.members_btn_text || "View Full list"}
          </Link>
        </div>

        {/* Limitation Notice */}
        {config?.members_notice && (
          <div className="w-full px-4 sm:px-6 lg:px-[110px] mb-4 mt-8">
            <div className="mx-auto border-2 border-solid border-gray-500 rounded-xl p-4 text-red-500 text-center text-sm md:text-base">
              {config.members_notice}
            </div>
          </div>
        )}
      </section>

      {/* <!-- Notices & Announcements Section --> */}
      <section>
        <div className="px-4 sm:px-6 lg:px-[110px] py-10">
          <div className="border-2 border-black rounded-3xl overflow-hidden bg-gray-100">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
              {/* Announcements block */}
              <div className="p-6 md:p-8 border-b md:border-b lg:border-r border-black">
                <div className="bg-green-500 text-white flex items-center gap-3 px-6 py-3 rounded-full mb-6 w-full justify-center">
                  <img src="/assets/images/img24.png" alt="Announcements Icon" width="28" />
                  <h3 className="text-lg md:text-xl font-semibold">Announcements</h3>
                </div>

                <div>
                  <DocumentsList
                    type="announcements"
                    limit={10}
                    showMoreHref="/notices-announcements#announcements"
                  />
                </div>
              </div>

              {/* Notices block */}
              <div className="p-6 md:p-8 border-b lg:border-b-0 lg:border-r border-black">
                <div className="bg-green-500 text-white flex items-center gap-3 px-6 py-3 rounded-full mb-6 w-full justify-center">
                  <img src="/assets/images/img57.png" alt="Notices Icon" width="22.4" />
                  <h3 className="text-lg md:text-xl font-semibold">Notices</h3>
                </div>

                <div>
                  <DocumentsList
                    type="notices"
                    limit={10}
                    showMoreHref="/notices-announcements#notices"
                  />
                </div>
              </div>

              {/* Reports block */}
              <div className="p-6 md:p-8">
                <div className="bg-green-500 text-white flex items-center gap-3 px-6 py-3 rounded-full mb-6 w-full justify-center">
                  <img src="/assets/images/reports.png" alt="Reports Icon" width="30" />
                  <h3 className="text-lg md:text-xl font-semibold">Reports</h3>
                </div>

                <div>
                  <DocumentsList
                    type="reports"
                    limit={10}
                    showMoreHref="/notices-announcements#reports"
                  />
                </div>
              </div>
            </div>

            {/* Bottom Note */}
            <div className="border-t border-black p-4 text-center text-red-500 text-sm md:text-lg">
              {config?.docs_bottom_note || "*Note:- If you can’t find any announcement, notices or reports visit the official sections."}
            </div>
          </div>
        </div>
      </section>

      {/* Hospital Slider Section */}
      <section>
        <HospitalCarousel
          images={carouselImages}
          autoplay={config?.carousel_autoplay ?? true}
          autoplaySpeed={config?.carousel_autoplay_speed ?? 3000}
          title={config?.carousel_title || "Critical Care Technology Professionals working in hospital settings"}
        />
        
        <div className="w-full px-4 sm:px-6 md:px-10 lg:px-[110px]">
          {/* Stats Section */}
          <MemberStats />
        </div>
      </section>

      {/* Features Cards Section */}
      <section className="px-4 sm:px-6 md:px-10 lg:px-[110px] py-10">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {cards.map((c: any, idx: number) => (
            <div key={idx} className="border border-gray-300 p-4 rounded-xl">
              <div className="rounded-xl overflow-hidden">
                <img
                  src={c.image || "/assets/images/placeholder-card.png"}
                  alt={c.title}
                  className="h-[200px] sm:h-[220px] md:h-[240px] lg:h-[260px] w-full object-cover rounded-xl"
                />
              </div>

              <h3
                className="font-semibold mt-4 text-[20px] md:text-[22px]"
                style={{ fontFamily: '"Times New Roman", Times, serif' }}
              >
                {c.title}
              </h3>

              <p
                className="mt-3 text-gray-700 text-justify text-[16px] md:text-[18px]"
                style={{ fontFamily: '"Times New Roman", Times, serif' }}
              >
                {c.description}
              </p>
            </div>
          ))}
        </div>
      </section>

      {/* Custom CSS Rules rendered right before body/container closes */}
      {pageData?.custom_css && (
        <style dangerouslySetInnerHTML={{ __html: pageData.custom_css }} />
      )}
    </div>
  );
}
