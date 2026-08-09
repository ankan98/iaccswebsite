"use client";

import Link from "next/link";
import { useState, useEffect } from "react";
import { buildPhpUrl, resolveAssetUrl, getPhpBaseUrl } from "@/app/lib/utils";

interface SocialLink {
  image: string;
  link: string;
  order: number;
}

interface Settings {
  site_logo: string;
  site_title: string;
  site_title_hindi: string;
  address: string;
  footer_text: string;
  last_updated_on: string;
  social_links: Record<string, SocialLink>;
}

interface MenuItem {
  id: number;
  title: string;
  url: string;
  icon?: string;
  parent_id?: number | null;
  children?: MenuItem[];
}

interface HeaderClientProps {
  settings: Settings | null;
  menuItems?: MenuItem[];
  backendUrl: string;
}

export default function HeaderClient({ settings, menuItems = [], backendUrl }: HeaderClientProps) {
  const [isOpen, setIsOpen] = useState(false);
  const [currentSettings, setCurrentSettings] = useState<Settings | null>(settings);
  const [currentMenuItems, setCurrentMenuItems] = useState<MenuItem[]>(menuItems);

  useEffect(() => {
    if (settings) setCurrentSettings(settings);
    if (menuItems && menuItems.length > 0) setCurrentMenuItems(menuItems);

    const apiBase = getPhpBaseUrl();
    fetch(`${apiBase}/get_settings.php`)
      .then((res) => (res.ok ? res.json() : null))
      .then((data) => {
        if (data) setCurrentSettings(data);
      })
      .catch(() => {});

    fetch(`${apiBase}/get_menus.php?menu_id=1`)
      .then((res) => (res.ok ? res.json() : null))
      .then((data) => {
        if (data?.items) setCurrentMenuItems(data.items);
      })
      .catch(() => {});
  }, [settings, menuItems]);

  const siteTitle       = currentSettings?.site_title       || "The Association for Critical Care Sciences";
  const siteTitleHindi  = currentSettings?.site_title_hindi || "दि एसोसिएशन फ़ॉर क्रिटिकल केयर साइंसेज़";
  const logoSrc = resolveAssetUrl(currentSettings?.site_logo, "/iaccslogo.png");

  const renderIcon = (iconPath?: string, defaultImg?: string) => {
    if (iconPath) {
      const src = iconPath.startsWith("http") ? iconPath : `${backendUrl}/${iconPath.replace(/^\/+/, '')}`;
      return <img src={src} alt="Navigation Icon" width="22" height="20" className="mr-2 object-contain shrink-0" />;
    }
    if (defaultImg) {
      return <img src={defaultImg} alt="Navigation Icon" width="22" height="20" className="mr-2 object-contain shrink-0" />;
    }
    return null;
  };

  const isExternalUrl = (url: string) => {
    return url.startsWith("http://") || url.startsWith("https://") || url.includes(".php");
  };

  return (
    <>
      {/* Top Header Logo Bar */}
      <nav
        style={{ borderBottom: "dashed" }}
        className="relative top-0 left-0 z-[9] flex w-full items-center justify-between border-b border-[#00000066] bg-white px-6 py-4 backdrop-blur-[15px] md:px-[110px]"
      >
        {/* Logo */}
        <div className="text-3xl font-bold text-black flex gap-2.5 items-center">
          <div>
            <Link href="/">
              <img
                src={logoSrc}
                alt="IACCS Site Logo"
                width={100}
                height={100}
                onError={(e) => {
                  (e.target as HTMLImageElement).src = "/iaccslogo.png";
                }}
              />
            </Link>
          </div>
          <div className="flex flex-col gap-1 md:gap-2.5">
            <h4 className="text-[15px] leading-[1.3] md:text-[21px] md:leading-normal font-bold">
              {siteTitle}
            </h4>
            {siteTitleHindi && (
              <h6 className="text-[15px] leading-[1.4] md:text-[20px] md:leading-normal">
                {siteTitleHindi}
              </h6>
            )}
          </div>
        </div>



        {/* Mobile Hamburger */}
        <button
          aria-label="Open Navigation Menu"
          className="flex flex-col gap-1 md:hidden"
          onClick={() => setIsOpen(true)}
        >
          <span className="h-[2px] w-6 bg-black"></span>
          <span className="h-[2px] w-6 bg-black"></span>
          <span className="h-[2px] w-6 bg-black"></span>
        </button>
      </nav>

      {/* Desktop Navigation */}
      <nav className="px-6 py-4 hidden lg:block lg:px-[110px]">
        <ul className="flex gap-4 justify-between items-center flex-wrap">
          {currentMenuItems.length > 0 ? (
            currentMenuItems.map((item) => {
              const hasChildren = item.children && item.children.length > 0;
              const isExt = isExternalUrl(item.url);

              return (
                <li key={item.id} className="relative group py-1">
                  {isExt ? (
                    <a
                      href={item.url.includes("login.php") ? buildPhpUrl("login.php") : item.url}
                      className="flex items-center text-gray-800 font-medium hover:text-blue-600 transition-colors py-1"
                    >
                      {renderIcon(item.icon)}
                      <span>{item.title}</span>
                      {hasChildren && <span className="ml-1 text-[10px] text-gray-400 group-hover:text-blue-600">▼</span>}
                    </a>
                  ) : (
                    <Link
                      href={item.url}
                      className="flex items-center text-gray-800 font-medium hover:text-blue-600 transition-colors py-1"
                    >
                      {renderIcon(item.icon)}
                      <span>{item.title}</span>
                      {hasChildren && <span className="ml-1 text-[10px] text-gray-400 group-hover:text-blue-600">▼</span>}
                    </Link>
                  )}

                  {/* Desktop Dropdown Sub-menu */}
                  {hasChildren && (
                    <div className="absolute left-0 top-full pt-1 hidden group-hover:block z-50 min-w-[210px] animate-fadeIn">
                      <div className="bg-white border border-gray-200 rounded-xl shadow-xl py-2 flex flex-col divide-y divide-gray-100">
                        {item.children!.map((child) => {
                          const isChildExt = isExternalUrl(child.url);
                          return isChildExt ? (
                            <a
                              key={child.id}
                              href={child.url.includes("login.php") ? buildPhpUrl("login.php") : child.url}
                              className="px-4 py-2.5 hover:bg-blue-50/80 text-gray-800 text-sm font-medium transition-colors flex items-center gap-2"
                            >
                              {renderIcon(child.icon)}
                              <span>{child.title}</span>
                            </a>
                          ) : (
                            <Link
                              key={child.id}
                              href={child.url}
                              className="px-4 py-2.5 hover:bg-blue-50/80 text-gray-800 text-sm font-medium transition-colors flex items-center gap-2"
                            >
                              {renderIcon(child.icon)}
                              <span>{child.title}</span>
                            </Link>
                          );
                        })}
                      </div>
                    </div>
                  )}
                </li>
              );
            })
          ) : (
            /* Fallback Static Links */
            <>
              <li>
                <Link className="flex items-center hover:text-blue-600 font-medium" href="/">
                  <img src="/assets/images/img23.jpg" alt="" width="25" height="20" className="mr-2" />
                  Home
                </Link>
              </li>
              <li>
                <Link className="flex items-center hover:text-blue-600 font-medium" href="/notices-announcements">
                  <img src="/assets/images/img24.jpg" alt="" width="25" height="20" className="mr-2" />
                  Notices & Announcements
                </Link>
              </li>
              <li>
                <Link className="flex items-center hover:text-blue-600 font-medium" href="/membership">
                  <img src="/assets/images/img25.jpg" alt="" width="25" height="20" className="mr-2" />
                  Membership
                </Link>
              </li>
              <li>
                <Link className="flex items-center hover:text-blue-600 font-medium" href="/membership-status">
                  <img src="/assets/images/reviewer-male.png" alt="" width="22" height="20" className="mr-2" />
                  Application Status
                </Link>
              </li>
              <li>
                <Link className="flex items-center hover:text-blue-600 font-medium" href="/about-us">
                  <img src="/assets/images/img26.jpg" alt="" width="27" height="20" className="mr-2" />
                  About Us
                </Link>
              </li>
              <li>
                <a className="flex items-center hover:text-blue-600 font-medium" href={buildPhpUrl("login.php")}>
                  <img src="/assets/images/img27.jpg" alt="" width="30" height="20" className="mr-2" />
                  Admin Login
                </a>
              </li>
              <li>
                <Link className="flex items-center hover:text-blue-600 font-medium" href="/contact-us">
                  <img src="/assets/images/img28.jpg" alt="" width="23" height="20" className="mr-2" />
                  Contact Us
                </Link>
              </li>
            </>
          )}
        </ul>
      </nav>

      {/* Mobile Backdrop */}
      <div
        className={`fixed inset-0 z-[99] bg-black/40 transition-opacity ${
          isOpen ? "opacity-100 visible" : "opacity-0 invisible"
        }`}
        onClick={() => setIsOpen(false)}
      />

      {/* Mobile Navigation Drawer */}
      <div
        className={`fixed right-0 top-0 z-[100] h-full w-[280px] bg-white p-6 shadow-2xl transition-transform duration-300 overflow-y-auto ${
          isOpen ? "translate-x-0" : "translate-x-full"
        }`}
      >
        <button
          aria-label="Close Navigation Menu"
          className="mb-6 text-right text-xl w-full flex justify-end font-bold text-gray-500 hover:text-black"
          onClick={() => setIsOpen(false)}
        >
          ✕
        </button>

        <nav className="flex flex-col gap-4">
          {currentMenuItems.length > 0 ? (
            currentMenuItems.map((item) => {
              const hasChildren = item.children && item.children.length > 0;
              const isExt = isExternalUrl(item.url);

              return (
                <div key={item.id} className="flex flex-col space-y-2">
                  {isExt ? (
                    <a
                      onClick={() => setIsOpen(false)}
                      href={item.url.includes("login.php") ? buildPhpUrl("login.php") : item.url}
                      className="flex items-center font-semibold text-gray-900 hover:text-blue-600 transition-colors py-1"
                    >
                      {renderIcon(item.icon)}
                      <span>{item.title}</span>
                    </a>
                  ) : (
                    <Link
                      onClick={() => setIsOpen(false)}
                      href={item.url}
                      className="flex items-center font-semibold text-gray-900 hover:text-blue-600 transition-colors py-1"
                    >
                      {renderIcon(item.icon)}
                      <span>{item.title}</span>
                    </Link>
                  )}

                  {/* Mobile Sub-menu Items */}
                  {hasChildren && (
                    <div className="pl-6 flex flex-col space-y-2 border-l-2 border-gray-100">
                      {item.children!.map((child) => {
                        const isChildExt = isExternalUrl(child.url);
                        return isChildExt ? (
                          <a
                            key={child.id}
                            onClick={() => setIsOpen(false)}
                            href={child.url.includes("login.php") ? buildPhpUrl("login.php") : child.url}
                            className="flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 transition-colors py-0.5"
                          >
                            {renderIcon(child.icon)}
                            <span>{child.title}</span>
                          </a>
                        ) : (
                          <Link
                            key={child.id}
                            onClick={() => setIsOpen(false)}
                            href={child.url}
                            className="flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 transition-colors py-0.5"
                          >
                            {renderIcon(child.icon)}
                            <span>{child.title}</span>
                          </Link>
                        );
                      })}
                    </div>
                  )}
                </div>
              );
            })
          ) : (
            /* Mobile Fallback Static Links */
            <>
              <Link onClick={() => setIsOpen(false)} href="/" className="flex items-center font-medium text-gray-800 hover:text-blue-600 transition-colors">
                <img src="/assets/images/img23.jpg" alt="" width="25" height="20" className="mr-2" />
                Home
              </Link>
              <Link onClick={() => setIsOpen(false)} href="/notices-announcements" className="flex items-center font-medium text-gray-800 hover:text-blue-600 transition-colors">
                <img src="/assets/images/img24.jpg" alt="" width="25" height="20" className="mr-2" />
                Notices & Announcements
              </Link>
              <Link onClick={() => setIsOpen(false)} href="/membership" className="flex items-center font-medium text-gray-800 hover:text-blue-600 transition-colors">
                <img src="/assets/images/img25.jpg" alt="" width="25" height="20" className="mr-2" />
                Membership
              </Link>
              <Link onClick={() => setIsOpen(false)} href="/membership-status" className="flex items-center font-medium text-gray-800 hover:text-blue-600 transition-colors">
                <img src="/assets/images/reviewer-male.png" alt="" width="22" height="20" className="mr-2" />
                Application Status
              </Link>
              <Link onClick={() => setIsOpen(false)} href="/about-us" className="flex items-center font-medium text-gray-800 hover:text-blue-600 transition-colors">
                <img src="/assets/images/img26.jpg" alt="" width="27" height="20" className="mr-2" />
                About Us
              </Link>
              <a onClick={() => setIsOpen(false)} href={buildPhpUrl("login.php")} className="flex items-center font-medium text-gray-800 hover:text-blue-600 transition-colors">
                <img src="/assets/images/img27.jpg" alt="" width="30" height="20" className="mr-2" />
                Admin Login
              </a>
              <Link onClick={() => setIsOpen(false)} href="/contact-us" className="flex items-center font-medium text-gray-800 hover:text-blue-600 transition-colors">
                <img src="/assets/images/img28.jpg" alt="" width="25" height="20" className="mr-2" />
                Contact Us
              </Link>
            </>
          )}
        </nav>
      </div>
    </>
  );
}
