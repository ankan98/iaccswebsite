"use client";

import Link from "next/link";
import { useState } from "react";

export default function Header() {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <>
      {/* Header */}
      <nav className="fixed top-0 left-0 z-[9] flex w-full items-center justify-between border-b border-[#00000066] bg-white px-6 py-4 backdrop-blur-[15px] md:px-[110px]">
        {/* Logo */}
        <Link href="/" className="text-3xl font-bold text-black">
          <img src="/iaccslogo.png" alt="Iaccslogo" width={100} height={100} />
        </Link>

        {/* Desktop Menu */}
        <div className="hidden items-center gap-10 md:flex">
          <Link className="font-medium text-[#525560]" href="/">
            Home
          </Link>
          <Link className="font-medium text-secondary-text" href="/about-us">
            About us
          </Link>
          <Link className="font-medium text-secondary-text" href="/membership">
            Membership
          </Link>
          <Link className="font-medium text-secondary-text" href="/contact-us">
            Contact
          </Link>
        </div>

        {/* Desktop Button */}
        <div className="hidden md:block">
          <Link
            href="/membership"
            className="rounded bg-gray-800 px-8 py-3 text-white hover:bg-opacity-90"
          >
            Join Us
          </Link>
        </div>

        {/* Mobile Hamburger */}
        <button
          className="flex flex-col gap-1 md:hidden"
          onClick={() => setIsOpen(true)}
        >
          <span className="h-[2px] w-6 bg-black"></span>
          <span className="h-[2px] w-6 bg-black"></span>
          <span className="h-[2px] w-6 bg-black"></span>
        </button>
      </nav>

      {/* Mobile Drawer */}
      <div
        className={`fixed inset-0 z-[99] bg-black/40 transition-opacity ${
          isOpen ? "opacity-100 visible" : "opacity-0 invisible"
        }`}
        onClick={() => setIsOpen(false)}
      />

      <div
        className={`fixed right-0 top-0 z-[100] h-full w-[260px] bg-white p-6 shadow-lg transition-transform duration-300 ${
          isOpen ? "translate-x-0" : "translate-x-full"
        }`}
      >
        {/* Close Button */}
        <button
          className="mb-6 text-right text-xl"
          onClick={() => setIsOpen(false)}
        >
          ✕
        </button>

        {/* Mobile Menu Links */}
        <nav className="flex flex-col gap-6">
          <Link onClick={() => setIsOpen(false)} href="/">
            Home
          </Link>
          <Link onClick={() => setIsOpen(false)} href="/about-us">
            About us
          </Link>
          <Link onClick={() => setIsOpen(false)} href="/membership">
            Membership
          </Link>
          <Link onClick={() => setIsOpen(false)} href="/contact-us">
            Contact
          </Link>

          {/* Mobile Donate Button */}
          <Link
            href="/membership"
            className="mt-4 rounded bg-gray-800 px-6 py-3 text-center text-white"
            onClick={() => setIsOpen(false)}
          >
            Join Us
          </Link>
        </nav>
      </div>
    </>
  );
}
