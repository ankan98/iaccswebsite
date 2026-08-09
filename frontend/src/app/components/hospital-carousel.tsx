"use client";

import { useState, useEffect, useRef } from "react";

interface CarouselProps {
  images: string[];
  autoplay: boolean;
  autoplaySpeed: number;
  title: string;
}

export default function HospitalCarousel({ images, autoplay, autoplaySpeed, title }: CarouselProps) {
  const [currentIndex, setCurrentIndex] = useState(0);
  const [visibleSlides, setVisibleSlides] = useState(3);
  const timerRef = useRef<NodeJS.Timeout | null>(null);

  const items = images && images.length > 0 ? images : [
    "/assets/images/img297.jpg",
    "/assets/images/img300.jpg",
    "/assets/images/img303.jpg"
  ];

  // Dynamic calculation of visible slides based on screen width
  useEffect(() => {
    const handleResize = () => {
      if (window.innerWidth < 768) {
        setVisibleSlides(1);
      } else if (window.innerWidth < 1024) {
        setVisibleSlides(2);
      } else {
        setVisibleSlides(3);
      }
    };

    handleResize(); // call initially
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  const maxIndex = Math.max(0, items.length - visibleSlides);

  const nextSlide = () => {
    setCurrentIndex((prev) => {
      if (prev >= maxIndex) {
        return 0; // Loop back to start
      }
      return prev + 1;
    });
  };

  const prevSlide = () => {
    setCurrentIndex((prev) => {
      if (prev === 0) {
        return maxIndex; // Loop to end
      }
      return prev - 1;
    });
  };

  useEffect(() => {
    // Reset index if it exceeds maxIndex (e.g. after screen resize)
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
    <div className="w-full px-4 sm:px-6 md:px-10 lg:px-[110px] mb-10">
      {title && (
        <h2 className="text-center text-xl md:text-2xl font-semibold mb-6 text-gray-800">
          {title}
        </h2>
      )}
      
      <div className="relative w-full px-10 md:px-12 lg:px-14 group">
        {/* Slider Track Wrapper */}
        <div className="overflow-hidden w-full">
          <div 
            className="flex transition-transform duration-500 ease-in-out"
            style={{ 
              transform: `translateX(-${currentIndex * (100 / visibleSlides)}%)` 
            }}
          >
            {items.map((img, idx) => (
              <div
                key={idx}
                style={{ width: `${100 / visibleSlides}%` }}
                className="shrink-0 px-1 md:px-1.5 lg:px-2"
              >
                <div className="relative aspect-[3/4] md:h-[350px] lg:h-[500px] w-full overflow-hidden bg-slate-50">
                  <img
                    src={img}
                    alt={`Hospital Setting ${idx + 1}`}
                    className="w-full h-full object-cover"
                  />
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
    </div>
  );
}
