"use client";

import Link from "next/link";
import { useState } from "react";

export default function Header() {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <>
      {/* Header */}
      <nav
        style={{ borderBottom: "dashed" }}
        className="relative top-0 left-0 z-[9] flex w-full items-center justify-between border-b border-[#00000066] bg-white px-6 py-4 backdrop-blur-[15px] md:px-[110px]"
      >
        {/* Logo */}
        <div className="text-3xl font-bold text-black flex gap-2.5 items-center">
          <div>
            <Link href="/">
              <img
                src="/iaccslogo.png"
                alt="Iaccslogo"
                width={100}
                height={100}
              />
            </Link>
          </div>
          <div className="flex flex-col gap-1 flex-row md:gap-2.5">
            <h4 className="text-[15px] leading-[1.3] md:text-[21px] md:leading-normal font-bold">
              The Association for Critical Care Sciences
            </h4>
            <h6 className="text-[15px] leading-[1.4] md:text-[20px] md:leading-normal">
              दि एसोसिएशन फ़ॉर क्रिटिकल केयर साइंसेज़
            </h6>
          </div>
        </div>

        {/* Desktop Menu */}
        {/* Desktop Button */}
        <div className="hidden md:block">
          <img src="images/img30.jpg" alt="" />
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
      <nav className="px-6 py-4 hidden lg:block lg:px-[110px] ">
        <ul className="flex gap-2.5 justify-between">
          <li>
            <Link className="flex" href="/">
              <img
                src="/assets/images/img23.jpg"
                alt=""
                width="25"
                height="20"
                className="mr-2"
              />
              Home
            </Link>
          </li>
          <li>
            <Link className="flex" href="/notices-announcements">
              <img
                src="/assets/images/img24.jpg"
                alt=""
                width="25"
                height="20"
                className="mr-2"
              />
              Notices & Announcements
            </Link>
          </li>
          <li>
            <Link className="flex" href="/membership">
              <img
                src="/assets/images/img25.jpg"
                alt=""
                width="25"
                height="20"
                className="mr-2"
              />
              Membership
            </Link>
          </li>
          <li>
            <Link className="flex" href="/membership-status">
              <img
                src="/assets/images/reviewer-male.png"
                alt=""
                width="22"
                height="20"
                className="mr-2"
              />
              Application Status
            </Link>
          </li>
          <li>
            <Link className="flex" href="/about-us">
              <img
                src="/assets/images/img26.jpg"
                alt=""
                width="27"
                height="20"
                className="mr-2"
              />
              About Us
            </Link>
          </li>
          <li>
            <a className="flex" href="/login.php">
              <img
                src="/assets/images/img27.jpg"
                alt=""
                width="30"
                height="20"
                className="mr-2"
              />
              Admin Login
            </a>
          </li>
          <li>
            <Link className="flex" href="/contact-us">
              <img
                src="/assets/images/img28.jpg"
                alt=""
                width="23"
                height="20"
                className="mr-2"
              />
              Contact Us
            </Link>
          </li>
        </ul>
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
        <nav className="flex flex-col gap-6 px-6">
          <Link
            onClick={() => setIsOpen(false)}
            href="/"
            className="flex items-center font-medium text-gray-800 hover:text-blue-600 transition-colors"
          >
            <img
              src="/assets/images/img23.jpg"
              alt=""
              width="25"
              height="20"
              className="mr-2"
            />
            Home
          </Link>

          <Link
            onClick={() => setIsOpen(false)}
            href="/notices-announcements"
            className="flex items-center font-medium text-gray-800 hover:text-blue-600 transition-colors"
          >
            <img
              src="/assets/images/img24.jpg"
              alt=""
              width="25"
              height="20"
              className="mr-2"
            />
            Notices & Announcements
          </Link>

          <Link
            onClick={() => setIsOpen(false)}
            href="/membership"
            className="flex items-center font-medium text-gray-800 hover:text-blue-600 transition-colors"
          >
            <img
              src="/assets/images/img25.jpg"
              alt=""
              width="25"
              height="20"
              className="mr-2"
            />
            Membership
          </Link>

          <Link
            onClick={() => setIsOpen(false)}
            href="/membership-status"
            className="flex items-center font-medium text-gray-800 hover:text-blue-600 transition-colors"
          >
            <img
              src="/assets/images/reviewer-male.png"
              alt=""
              width="22"
              height="20"
              className="mr-2"
            />
            Application Status
          </Link>

          <Link
            onClick={() => setIsOpen(false)}
            href="/about-us"
            className="flex items-center font-medium text-gray-800 hover:text-blue-600 transition-colors"
          >
            <img
              src="/assets/images/img26.jpg"
              alt=""
              width="27"
              height="20"
              className="mr-2"
            />About us
          </Link>

          <Link
            onClick={() => setIsOpen(false)}
            href="/login.php"
            className="flex items-center font-medium text-gray-800 hover:text-blue-600 transition-colors"
          >
            <img
              src="/assets/images/img27.jpg"
              alt=""
              width="30"
              height="20"
              className="mr-2"
            />Admin Login
          </Link>
          <Link
            onClick={() => setIsOpen(false)}
            href="/contact-us"
            className="flex items-center font-medium text-gray-800 hover:text-blue-600 transition-colors"
          >
            <img
              src="/assets/images/img28.jpg"
              alt=""
              width="25"
              height="20"
              className="mr-2"
            />
            Contact Us
          </Link>

          {/* Mobile Donate Button */}
        </nav>
      </div>
    </>
  );
}
