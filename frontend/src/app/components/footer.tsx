import Link from "next/link";

async function getSettings() {
  const backendUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || "https://iaccs.org.in";
  try {
    const res = await fetch(`${backendUrl}/get_settings.php`);
    if (!res.ok) return null;
    return await res.json();
  } catch (e) {
    console.error("Failed to load footer settings:", e);
    return null;
  }
}

async function getFooterMenu() {
  const backendUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || "https://iaccs.org.in";
  try {
    const res = await fetch(`${backendUrl}/get_menus.php?menu_id=2`);
    if (!res.ok) return [];
    const data = await res.json();
    return data.items || [];
  } catch (e) {
    console.error("Failed to load footer menu:", e);
    return [];
  }
}

// Fallback static social icons (when no CMS image is uploaded)
const STATIC_ICONS: Record<string, string> = {
  instagram: "/assets/images/img86.png",
  facebook:  "/assets/images/img85.png",
  linkedin:  "/assets/images/img87.png",
  x:         "/assets/images/img88.jpg",
};

const SOCIAL_NAMES: Record<string, string> = {
  facebook:  "Facebook",
  instagram: "Instagram",
  linkedin:  "LinkedIn",
  x:         "X",
};

export default async function Footer() {
  const backendUrl = process.env.NEXT_PUBLIC_PHP_BACKEND_URL || "https://iaccs.org.in";
  const settings = await getSettings();
  const footerMenuItems = await getFooterMenu();

  const address        = settings?.address        || "Mathkal, Nazrul Sarani, Dumdum\nCantonment, Kolkata, 700065";
  const footerText     = settings?.footer_text    || "";
  const lastUpdated    = settings?.last_updated_on || "20/03/2026";
  const socialLinks    = settings?.social_links   || {};

  // Sorted social entries
  const sortedSocials = Object.entries(socialLinks as Record<string, { image: string; link: string; order: number }>)
    .filter(([, v]) => v.link)
    .sort(([, a], [, b]) => (a.order ?? 0) - (b.order ?? 0));

  return (
    <footer id="contact" className="w-full">

      {/* Top strip */}
      <div className="bg-[#1a18a8] text-white px-4 sm:px-6 md:px-12 lg:px-[110px] py-8 md:py-10">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 md:gap-10 text-[15px]">

          {/* Quick Links */}
          <div>
            <h3 className="text-xl md:text-2xl font-semibold mb-4">Quick Links</h3>
            <ul className="space-y-2 md:space-y-3 leading-relaxed">
              {footerMenuItems.length > 0 ? (
                footerMenuItems.map((item: any) => {
                  const isExt = item.url.startsWith("http://") || item.url.startsWith("https://");

                  return (
                    <li key={item.id}>
                      {isExt ? (
                        <a href={item.url} className="hover:underline inline-flex items-center">
                          <span className="mr-2">&gt;</span>
                          {item.title}
                        </a>
                      ) : (
                        <Link href={item.url} className="hover:underline inline-flex items-center">
                          <span className="mr-2">&gt;</span>
                          {item.title}
                        </Link>
                      )}

                      {item.children && item.children.length > 0 && (
                        <ul className="pl-4 pt-1.5 space-y-1">
                          {item.children.map((child: any) => {
                            const isChildExt = child.url.startsWith("http://") || child.url.startsWith("https://");
                            return (
                              <li key={child.id}>
                                {isChildExt ? (
                                  <a href={child.url} className="hover:underline inline-flex items-center text-xs opacity-90">
                                    <span className="mr-1.5">-</span>
                                    {child.title}
                                  </a>
                                ) : (
                                  <Link href={child.url} className="hover:underline inline-flex items-center text-xs opacity-90">
                                    <span className="mr-1.5">-</span>
                                    {child.title}
                                  </Link>
                                )}
                              </li>
                            );
                          })}
                        </ul>
                      )}
                    </li>
                  );
                })
              ) : (
                /* Fallback static quick links */
                <>
                  <li>
                    <Link href="/about-us" className="hover:underline inline-flex items-center">
                      <span className="mr-2">&gt;</span> About us
                    </Link>
                  </li>
                  <li>
                    <Link href="/refund-policy" className="hover:underline inline-flex items-center">
                      <span className="mr-2">&gt;</span> Refund Policy
                    </Link>
                  </li>
                  <li>
                    <Link href="/privacy-policy" className="hover:underline inline-flex items-center">
                      <span className="mr-2">&gt;</span> Privacy Policy
                    </Link>
                  </li>
                  <li>
                    <Link href="/terms-conditions" className="hover:underline inline-flex items-center">
                      <span className="mr-2">&gt;</span> Terms &amp; Conditions
                    </Link>
                  </li>
                </>
              )}
            </ul>
          </div>

          {/* Social Links */}
          <div className="text-left sm:text-center">
            <h3 className="text-xl md:text-2xl font-semibold mb-4">Social Links</h3>

            {sortedSocials.length > 0 ? (
              <div className="flex items-center sm:justify-center gap-5 md:gap-6 mb-4">
                {sortedSocials.map(([key, social]) => {
                  const cleanIconPath = social.image ? social.image.replace(/^\/?(cms\/)?/, '') : '';
                  const iconSrc = cleanIconPath
                    ? `${backendUrl}/${cleanIconPath}`
                    : STATIC_ICONS[key] || "";
                  if (!iconSrc) return null;
                  return (
                    <a
                      key={key}
                      href={social.link}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="hover:opacity-90"
                    >
                      <img
                        src={iconSrc}
                        alt={SOCIAL_NAMES[key] || key}
                        width={key === "x" ? 20 : 22}
                      />
                    </a>
                  );
                })}
              </div>
            ) : (
              /* Fallback static icons when nothing is configured */
              <div className="flex items-center sm:justify-center gap-5 md:gap-6 mb-4">
                <a href="https://www.instagram.com/accs_india" target="_blank" rel="noopener noreferrer" className="hover:opacity-90">
                  <img src="/assets/images/img86.png" alt="Instagram" width={22} />
                </a>
                <a href="https://www.facebook.com/share/1Eujhyvcd1/" target="_blank" rel="noopener noreferrer" className="hover:opacity-90">
                  <img src="/assets/images/img85.png" alt="Facebook" width={22} />
                </a>
                <a href="https://www.linkedin.com/company/iaccs-india/" target="_blank" rel="noopener noreferrer" className="hover:opacity-90">
                  <img src="/assets/images/img87.png" alt="LinkedIn" width={22} />
                </a>
                <a href="https://x.com/" target="_blank" rel="noopener noreferrer" className="hover:opacity-90">
                  <img src="/assets/images/img88.jpg" alt="X" width={20} />
                </a>
              </div>
            )}

            <p className="text-xs md:text-sm opacity-90">
              Last Updated on - {lastUpdated}
            </p>
          </div>

          {/* Our Office */}
          <div>
            <h3 className="text-xl md:text-2xl font-semibold mb-4">Our Office</h3>
            <div className="flex items-start gap-3">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" className="mt-1 flex-shrink-0">
                <path
                  d="M12 2C7.6 2 4 5.6 4 10c0 5.3 6.8 11.5 7.1 11.8.5.4 1.3.4 1.8 0C13.2 21.5 20 15.3 20 10c0-4.4-3.6-8-8-8Zm0 11.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7Z"
                  fill="#fff"
                />
              </svg>
              <address className="not-italic text-sm md:text-base leading-relaxed whitespace-pre-line">
                {address}
              </address>
            </div>
          </div>

        </div>
      </div>

      {/* Bottom strip */}
      <div className="bg-[#226022] text-white px-4 sm:px-6 md:px-12 lg:px-[110px] py-4 text-xs md:text-sm text-center md:text-left">
        {footerText ? (
          <div dangerouslySetInnerHTML={{ __html: footerText }} />
        ) : (
          <>
            <p className="mb-1">Registered as an AOP for regulatory purposes</p>
            <p>
              2025 ©{" "}
              <Link href="/" className="underline">
                The Association For Critical Care Sciences (The ACCS)
              </Link>
              , All Right Reserved.
            </p>
          </>
        )}
      </div>

    </footer>
  );
}