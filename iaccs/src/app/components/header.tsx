import Link from "next/link";


export default function Header() {
    return (
   <nav className="flex z-[9] items-center justify-between w-full px-[110px] py-[18px] fixed top-0 left-0 bg-white border-b border-[#00000066] backdrop-blur-[15px] backdrop-brightness-[100%]">
        <a className="flex items-center w-[129px] h-11" href="#home">
            <div className="font-bold text-[#000000] text-2xl tracking-[0] leading-[44px] whitespace-nowrap">
                largerthan
            </div>
            <div className="font-normal text-[#000000] text-[25px] tracking-[0] leading-[44px] whitespace-nowrap">
                i
            </div>
        </a>

        <nav className="flex items-center justify-center gap-10 px-5 py-4">
            <a className="font-medium text-base tracking-[0] leading-[normal] whitespace-nowrap text-[#525560]" href="/">Home</a>
            <Link className="font-medium text-base tracking-[0] leading-[normal] whitespace-nowrap text-secondary-text" href="/about-us">About us</Link>
            <Link className="font-medium text-base tracking-[0] leading-[normal] whitespace-nowrap text-secondary-text" href="#services">What We Do</Link>
            <a className="font-medium text-base tracking-[0] leading-[normal] whitespace-nowrap text-secondary-text" href="#media">Media</a>
            <Link className="font-medium text-base tracking-[0] leading-[normal] whitespace-nowrap text-secondary-text" href="/contact-us">Contact</Link>
        </nav>

        <button className="h-auto px-8 py-3 bg-gray-800 rounded hover:bg-opacity-90">
            <span className="font-medium text-white text-base text-right tracking-[0] leading-[normal] whitespace-nowrap">
                Donate
            </span>
        </button>
    </nav>
    );
  }
  