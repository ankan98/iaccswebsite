import Link from "next/link";

export default function Footer() {
  return (
    <footer id="contact" className="w-full">

  {/* Top strip */}
  <div className="bg-[#1a18a8] text-white px-4 sm:px-6 md:px-12 lg:px-[110px] py-8 md:py-10">

    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 md:gap-10 text-[15px]">

      {/* Quick Links */}
      <div>
        <h3 className="text-xl md:text-2xl font-semibold mb-4">Quick Links</h3>

        <ul className="space-y-2 md:space-y-3 leading-relaxed">
          <li>
            <Link href="/about-us" className="hover:underline">
              <span className="mr-2">&gt;</span> About us
            </Link>
          </li>

          <li>
            <Link href="/refund-policy" className="hover:underline">
              <span className="mr-2">&gt;</span> Refund Policy
            </Link>
          </li>

          <li>
            <Link href="/privacy-policy" className="hover:underline">
              <span className="mr-2">&gt;</span> Privacy Policy
            </Link>
          </li>

          <li>
            <Link href="/terms-conditions" className="hover:underline">
              <span className="mr-2">&gt;</span> Terms & Conditions
            </Link>
          </li>
        </ul>
      </div>

      {/* Social Links */}
      <div className="text-left sm:text-center">

        <h3 className="text-xl md:text-2xl font-semibold mb-4">Social Links</h3>

        <div className="flex items-center sm:justify-center gap-5 md:gap-6 mb-4">

          <a
            href="https://www.instagram.com/accs_india?igsh=cHh0d2ZsN3U1MWhr"
            target="_blank"
            rel="noopener noreferrer"
            className="hover:opacity-90"
          >
            <img src="/assets/images/img86.png" alt="Instagram" width={22} />
          </a>

          <a
            href="https://www.facebook.com/share/1Eujhyvcd1/"
            target="_blank"
            rel="noopener noreferrer"
            className="hover:opacity-90"
          >
            <img src="/assets/images/img85.png" alt="Facebook" width={22} />
          </a>

          <a
            href="https://www.linkedin.com/company/iaccs-india/"
            target="_blank"
            rel="noopener noreferrer"
            className="hover:opacity-90"
          >
            <img src="/assets/images/img87.png" alt="LinkedIn" width={22} />
          </a>

          <a
            href="https://x.com/"
            target="_blank"
            rel="noopener noreferrer"
            className="hover:opacity-90"
          >
            <img src="/assets/images/img88.jpg" alt="X" width={20} />
          </a>

        </div>

        <p className="text-xs md:text-sm opacity-90">
          Last Updated on - 20/03/2026
        </p>

      </div>

      {/* Our Office */}
      <div>

        <h3 className="text-xl md:text-2xl font-semibold mb-4">
          Our Office
        </h3>

        <div className="flex items-start gap-3">

          <svg
            width="22"
            height="22"
            viewBox="0 0 24 24"
            fill="none"
            className="mt-1 flex-shrink-0"
          >
            <path
              d="M12 2C7.6 2 4 5.6 4 10c0 5.3 6.8 11.5 7.1 11.8.5.4 1.3.4 1.8 0C13.2 21.5 20 15.3 20 10c0-4.4-3.6-8-8-8Zm0 11.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7Z"
              fill="#fff"
            />
          </svg>

          <address className="not-italic text-sm md:text-base leading-relaxed">
            Mathkal, Nazrul Sarani, Dumdum<br />
            Cantonment, Kolkata, 700065
          </address>

        </div>

      </div>

    </div>
  </div>

  {/* Bottom strip */}
  <div className="bg-[#226022] text-white px-4 sm:px-6 md:px-12 lg:px-[110px] py-4 text-xs md:text-sm text-center md:text-left">

    <p className="mb-1">
      Registered as an AOP for regulatory purposes
    </p>

    <p>
      2025 ©{" "}
      <Link href="/" className="underline">
        The Association For Critical Care Sciences (The ACCS)
      </Link>
      , All Right Reserved.
    </p>

  </div>

</footer>
  );
}