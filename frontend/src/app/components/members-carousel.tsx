"use client";

import { useState, useEffect, useRef } from "react";

interface Member {
  name: string;
  role: string;
  qualification: string;
  image: string;
}

interface MembersCarouselProps {
  members: Member[];
  autoplay?: boolean;
  autoplaySpeed?: number;
}

export default function MembersCarousel({ members, autoplay = true, autoplaySpeed = 4000 }: MembersCarouselProps) {
  const [currentIndex, setCurrentIndex] = useState(0);
  const [visibleSlides, setVisibleSlides] = useState(3);
  const timerRef = useRef<NodeJS.Timeout | null>(null);

  const items = members && members.length > 0 ? members : [];

  // Dynamic visible slides
  useEffect(() => {
    const handleResize = () => {
      if (window.innerWidth < 640) {
        setVisibleSlides(1);
      } else if (window.innerWidth < 1024) {
        setVisibleSlides(2);
      } else {
        setVisibleSlides(3);
      }
    };

    handleResize();
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  const maxIndex = Math.max(0, items.length - visibleSlides);

  const nextSlide = () => {
    setCurrentIndex((prev) => {
      if (prev >= maxIndex) {
        return 0;
      }
      return prev + 1;
    });
  };

  const prevSlide = () => {
    setCurrentIndex((prev) => {
      if (prev === 0) {
        return maxIndex;
      }
      return prev - 1;
    });
  };

  useEffect(() => {
    if (currentIndex > maxIndex) {
      setCurrentIndex(maxIndex);
    }
  }, [maxIndex, currentIndex]);

  useEffect(() => {
    if (autoplay && items.length > visibleSlides) {
      if (timerRef.current) clearInterval(timerRef.current);
      timerRef.current = setInterval(nextSlide, autoplaySpeed);
    }
    return () => {
      if (timerRef.current) clearInterval(timerRef.current);
    };
  }, [autoplay, autoplaySpeed, items.length, currentIndex, visibleSlides, maxIndex]);

  return (
    <div className="relative w-full px-10 md:px-12 lg:px-14 group mx-auto">
      {/* Slider Track Wrapper */}
      <div className="overflow-hidden w-full py-2">
        <div 
          className="flex transition-transform duration-500 ease-in-out"
          style={{ 
            transform: `translateX(-${currentIndex * (100 / visibleSlides)}%)` 
          }}
        >
          {items.map((m, idx) => (
            <div
              key={idx}
              style={{ width: `${100 / visibleSlides}%` }}
              className="shrink-0 px-2 flex justify-center"
            >
              <div className="bg-white rounded-xl shadow p-6 text-center w-full max-w-[300px] border border-gray-150 hover:shadow-md transition-shadow">
                <img
                  src={m.image || "/assets/images/placeholder-member.png"}
                  alt={m.name}
                  className="w-28 h-28 md:w-32 md:h-32 object-cover rounded-lg mx-auto"
                />
                <h3 className="mt-4 font-semibold text-gray-800 uppercase text-sm md:text-base">
                  {m.name}
                </h3>
                <p className="text-xs md:text-sm text-gray-600 font-medium">{m.role}</p>
                <p className="text-xs md:text-sm text-gray-500">{m.qualification}</p>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Navigation Arrows */}
      {items.length > 1 && (
        <>
          <button
            onClick={prevSlide}
            className="absolute left-0 top-1/2 -translate-y-1/2 z-20 bg-gray-400 hover:bg-gray-500 text-white w-9 h-14 md:w-11 md:h-16 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-300"
          >
            <span className="text-sm md:text-base">◀</span>
          </button>
          <button
            onClick={nextSlide}
            className="absolute right-0 top-1/2 -translate-y-1/2 z-20 bg-gray-400 hover:bg-gray-500 text-white w-9 h-14 md:w-11 md:h-16 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-300"
          >
            <span className="text-sm md:text-base">▶</span>
          </button>
        </>
      )}
    </div>
  );
}
