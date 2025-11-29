import Image from "next/image";

export default function About() {
  return (
   
  <>
  <header className="relative w-full flex flex-col gap-48 bg-transparent bg-[url(/assets/images/banner-img.png)] bg-cover bg-center py-32 mt-20 bg-gray-500">
      <div className="absolute inset-0 bg-black opacity-50"></div>
        <div className="container mx-auto px-[110px] relative z-[1]">
            <div className="w-full max-w-[642px] flex flex-col gap-12">
                <h1 className="w-full max-w-[640px] font-bold text-white text-[64px] tracking-[0] leading-[76.8px]">About Us</h1>
                <p className="text-lg text-white">Join us in providing life-saving medical care to communities without access to the basic healthcare they need.</p>
            </div>
        </div>
    </header>
    <section className="w-full flex flex-col gap-16 py-16 px-[110px]">
        <div className="w-full flex gap-[82px]">
            <div className="flex-1 flex flex-col gap-8 relative">
                <div className="flex items-center gap-8">
                    <div className="flex items-center gap-4">
                        <div className="w-[72px] h-0.5 bg-primary-text"></div>
                        <div className="font-bold text-primary-text text-base tracking-[2.00px] leading-[normal] whitespace-nowrap">
                            Know About Us
                        </div>
                    </div>
                </div>

                <h2 className="font-bold text-primary-text text-[48px] tracking-[0] leading-[120%]">Delivering Free Healthcare to Underserved Communities Worldwide</h2>

                <p className="text-secondary-text text-base tracking-[0] leading-[160%]">
                    We are a global non-profit dedicated to providing free healthcare to underserved populations. From remote villages to disaster zones, we’re committed to making healthcare accessible to everyone.
                    posuere.
                </p>

                <div>
                    <button className="bg-yellow-500 hover:opacity-90 text-black font-medium text-base px-8 py-4 rounded h-auto">
                        Learn more
                    </button>
                </div>
            </div>

            <div className="w-[480px] flex-shrink-0">
                <img className="w-full h-auto" alt="Video" src="/assets/images/about-us.png" />
            </div>
        </div>
    </section>
    </>
  );
}
