"use client";

import { useEffect, useState } from "react";
import Image from "next/image";

export default function PageLoader() {
  const [loading, setLoading] = useState(true);
  const [fadeOut, setFadeOut] = useState(false);

  useEffect(() => {
    // Smooth fade out after page load
    const timer = setTimeout(() => {
      setFadeOut(true);
      const removeTimer = setTimeout(() => {
        setLoading(false);
      }, 500);
      return () => clearTimeout(removeTimer);
    }, 600);

    return () => clearTimeout(timer);
  }, []);

  if (!loading) return null;

  return (
    <div
      id="main-page-loader"
      className={`fixed inset-0 z-[99999] flex flex-col items-center justify-center bg-slate-950/90 backdrop-blur-xl transition-all duration-500 ease-in-out ${
        fadeOut ? "opacity-0 pointer-events-none scale-105" : "opacity-100 scale-100"
      }`}
    >
      {/* Background radial glow */}
      <div className="absolute w-80 h-80 bg-[#38b6ff]/20 rounded-full blur-3xl animate-pulse pointer-events-none" />
      <div className="absolute w-52 h-52 bg-blue-600/10 rounded-full blur-2xl animate-ping pointer-events-none" />

      {/* Main Preloader Content */}
      <div className="relative flex flex-col items-center gap-5 z-10">
        {/* Outer Ring & Spinning Animation */}
        <div className="relative flex items-center justify-center size-24 sm:size-28">
          <div className="absolute inset-0 rounded-full border-2 border-transparent border-t-[#38b6ff] border-r-[#38b6ff]/50 animate-spin" />
          <div className="absolute inset-2 rounded-full border-2 border-transparent border-b-[#0072ff] border-l-[#0072ff]/50 animate-[spin_1.5s_linear_infinite_reverse]" />

          {/* Logo container */}
          <div className="relative size-16 sm:size-20 bg-white/10 dark:bg-slate-900/60 rounded-full p-2.5 shadow-2xl backdrop-blur-md flex items-center justify-center border border-white/20">
            <Image
              src="/iaccslogo.png"
              alt="IACCS Preloader Logo"
              width={70}
              height={70}
              className="object-contain animate-pulse"
              priority
              unoptimized
            />
          </div>
        </div>

        {/* Brand Name & Dots */}
        <div className="flex flex-col items-center gap-1.5 text-center">
          <span className="text-white text-sm sm:text-base font-bold tracking-wider">
            IACCS
          </span>
          <span className="text-[11px] text-slate-400 font-medium tracking-widest uppercase flex items-center gap-1">
            Loading
            <span className="inline-flex gap-1 ml-1">
              <span className="size-1.5 rounded-full bg-[#38b6ff] animate-bounce [animation-delay:-0.3s]" />
              <span className="size-1.5 rounded-full bg-[#38b6ff] animate-bounce [animation-delay:-0.15s]" />
              <span className="size-1.5 rounded-full bg-[#38b6ff] animate-bounce" />
            </span>
          </span>
        </div>
      </div>
    </div>
  );
}
