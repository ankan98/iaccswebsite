import Link from "next/link";


export default function Footer() {
  return (
  <footer id="contact" className="relative w-full bg-black xl:py-[86px] py-10 xl:px-[110px] md:px-20 px-5">
        <div className="flex flex-col lg:flex-row gap-12 lg:gap-0 justify-between items-start">
            <div className="flex items-start gap-1">
                <div className="font-bold text-white text-2xl tracking-[0] leading-[44px] whitespace-nowrap">
                    <img src="/iaccslogo.jpg" alt=""  />
                </div>
            </div>

            <nav className="flex gap-12 lg:gap-16">
                <div className="flex flex-col gap-6">
                    <Link href="/" className="font-bold text-base leading-[25.6px] text-white tracking-[0] whitespace-nowrap hover:opacity-100 transition-opacity">
                        Home
                    </Link>
                    <Link href="/about-us" className="font-normal text-sm leading-[22.4px] opacity-[0.78] text-white tracking-[0] whitespace-nowrap hover:opacity-100 transition-opacity">
                        About us
                    </Link>
                  
                    <Link href="/contact-us" className="font-normal text-sm leading-[22.4px] opacity-[0.78] text-white tracking-[0] whitespace-nowrap hover:opacity-100 transition-opacity">
                        Contact
                    </Link>
                </div>

             

                <div className="flex flex-col gap-6">
                    <div className="font-bold text-base leading-[25.6px] text-white tracking-[0] whitespace-nowrap hover:opacity-100 transition-opacity">
                        Connect
                    </div>
                    <a href="https://www.facebook.com/share/1Eujhyvcd1/"  target="_blank"
  rel="noopener noreferrer" className="font-normal text-sm leading-[22.4px] opacity-[0.78] text-white tracking-[0] whitespace-nowrap hover:opacity-100 transition-opacity">
                        Facebook
                    </a>
                    <a href="https://www.instagram.com/accs_india?igsh=cHh0d2ZsN3U1MWhr"  target="_blank"
  rel="noopener noreferrer" className="font-normal text-sm leading-[22.4px] opacity-[0.78] text-white tracking-[0] whitespace-nowrap hover:opacity-100 transition-opacity">
                        Instagram
                    </a>
                    
                    <a href="https://www.linkedin.com/company/iaccs-india/"  target="_blank"
  rel="noopener noreferrer" className="font-normal text-sm leading-[22.4px] opacity-[0.78] text-white tracking-[0] whitespace-nowrap hover:opacity-100 transition-opacity">
                        Linkdin
                    </a>
                </div>
            </nav>

            {/* <div className="flex flex-col gap-8 max-w-[546px] w-full">
                <h2 className="font-bold text-white text-[40px] tracking-[0] leading-[56px]">
                    Subscribe to get latest updates
                </h2>

                <div className="flex gap-2">
                    <input
                        type="email"
                        placeholder="Your email"
                        className="flex-1 h-16 bg-transparent border border-[#ebf0f94c] text-white placeholder:text-[#ebf0f94c] placeholder:opacity-80 font-normal text-base rounded px-4"
                    />
                    <button className="h-16 px-8 bg-white text-primary-text font-medium text-base rounded backdrop-blur-2xl backdrop-brightness-[100%] hover:opacity-90">
                        Subscribe
                    </button>
                </div>
            </div> */}
        </div>
    </footer>
  );
}
