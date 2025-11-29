import Image from "next/image";

export default function Home() {
  return (
   
    <>
    {/* <!-- Header Section --> */}
    <header className="relative w-full flex flex-col gap-48 bg-transparent bg-[url(/assets/images/banner-img.png)] bg-cover bg-center py-32 mt-20 bg-gray-500">
      <div className="absolute inset-0 bg-black opacity-50"></div>
        <div className="container mx-auto px-[110px] relative z-[1]">
            <div className="w-full max-w-[642px] flex flex-col gap-12">
                <h1 className="w-full max-w-[640px] font-bold text-white text-[64px] tracking-[0] leading-[76.8px]">Bringing Healthcare to the Worlds Most Vulnerable</h1>
                <p className="text-lg text-white">Join us in providing life-saving medical care to communities without access to the basic healthcare they need.</p>

                <div className="flex gap-6">
                    <button className="h-auto px-8 py-4 bg-white text-primary-text hover:bg-opacity-90 rounded backdrop-blur-2xl backdrop-brightness-[100%]">
                        <span className="font-medium text-base text-right tracking-[0] leading-[normal] whitespace-nowrap">
                            Volunteer Today
                        </span>
                    </button>

                    {/* <button className="inline-flex w-[140px] h-12 items-center justify-center gap-2 px-4 py-3 bg-transparent hover:opacity-80 transition-opacity">
                        <svg className="w-6 h-6 text-white fill-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                        <span className="font-medium text-white text-base text-right tracking-[0] leading-[normal] whitespace-nowrap">
                            Play Video
                        </span>
                    </button> */}
                </div>
            </div>
        </div>
    </header>

    {/* <!-- About Us Section --> */}
    <section id="about" className="w-full flex flex-col gap-16 py-16 px-[110px]">
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

    <section>
        <div className="max-w-[1500px] mx-auto px-[206px]">
            <div className="flex">
                <div className="flex items-center justify-center -rotate-90 ">
                    <div className="flex items-center gap-6">
                        <div className="font-bold text-primary-text text-base tracking-[2.00px] leading-[normal] whitespace-nowrap">
                            WHAT WE DO
                        </div>
                        <div className="w-[72px] h-0.5 bg-gray-500"></div>
                    </div>
                </div>

                <h2 className="font-bold text-primary-text text-[48px] tracking-[0] leading-[120%]">We bring free, high-quality medical care to underserved communities. Through mobile clinics, health education, and essential treatment programs, we help people live healthier, stronger lives.</h2>
            </div>
        </div>
    </section>

    {/* <!-- Services Section --> */}
    <section id="services" className="relative w-full bg-yellow-300 py-[147px] mt-[74px]">
        <div className="max-w-[1500px] mx-auto px-[206px]">
            <div className="flex items-start gap-[80px]">
                <div className="flex-1 flex flex-col gap-[32px]">
                    <div className="flex flex-col gap-4">
                        <h2 className="font-bold text-primary-text text-[48px] tracking-[0] leading-[120%]">How You Can Help</h2>
                        <p className="text-secondary-text text-base tracking-[0] leading-[160%]">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                            Suspendisse varius enim in eros elementum tristique.
                        </p>
                    </div>

                    <div className="relative pl-6">
                        <div className="absolute left-0 top-0 bottom-0 w-0.5 bg-primary-text"></div>

                        <div className="flex flex-col gap-[24px]">
                            <div className="flex gap-4">
                                {/* <img className="w-7 h-7 flex-shrink-0" alt="Family support" src="/icon-3.png" /> */}
                                <div className="flex flex-col gap-[10px]">
                                    <h3 className="font-bold text-primary-text text-2xl tracking-[0] leading-[normal]">
                                        Donate
                                    </h3>
                                    <p className="text-secondary-text text-base tracking-[0] leading-[160%] max-w-[384px]">Your generous donation ensures that we can provide medical supplies, hire trained professionals, and operate mobile clinics in underserved areas.</p>
                                </div>
                            </div>

                            <div className="flex gap-4">
                                {/* <img className="w-7 h-7 flex-shrink-0" alt="Health benefits" src="/icon-2.png" /> */}
                                <div className="flex flex-col gap-[10px]">
                                    <h3 className="font-bold text-primary-text text-2xl tracking-[0] leading-[normal]">Volunteer</h3>
                                    <p className="text-secondary-text text-base tracking-[0] leading-[160%] max-w-[384px]">We rely on volunteers from all over the world. Your skills, time, and heart can change lives.</p>
                                </div>
                            </div>

                            <div className="flex gap-4">
                                {/* <img className="w-7 h-7 flex-shrink-0" alt="Scholarships" src="/icon-1.png" /> */}
                                <div className="flex flex-col gap-[10px]">
                                    <h3 className="font-bold text-primary-text text-2xl tracking-[0] leading-[normal]">Spread the Word</h3>
                                    <p className="text-secondary-text text-base tracking-[0] leading-[160%] max-w-[384px]">Help raise awareness about our mission. Share our story and help us grow our community of supporters.</p>
                                </div>
                            </div>

                            {/* <div className="flex gap-4">
                                <img className="w-7 h-7 flex-shrink-0" alt="Therapy" src="/icon.png" />
                                <div className="flex flex-col gap-[10px]">
                                    <h3 className="font-bold text-primary-text text-2xl tracking-[0] leading-[normal]">
                                        Therapy
                                    </h3>
                                    <p className="text-secondary-text text-base tracking-[0] leading-[160%] max-w-[384px]">
                                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse varius enim in eros.
                                    </p>
                                </div>
                            </div> */}
                        </div>
                    </div>
                </div>

                <div className="flex-shrink-0">
                    <img className="w-[480px] h-[500px] rounded-[20px] object-cover" alt="Child" src="/assets/images/child-care.png" />
                </div>
            </div>
            
        </div>
    </section>

    {/* <!-- Projects Section --> */}
    <section className="relative w-full px-[110px] py-24">
        <div className="max-w-[1280px] mx-auto">
            <div className="flex items-start gap-8 mb-[99px]">
                <div className="flex items-center gap-6">
                    <div className="flex flex-col items-center gap-4">
                        <div className="font-bold text-primary-text text-base tracking-[2.00px] leading-[normal] whitespace-nowrap -rotate-90 origin-center">
                            PROJECTS WE HAVE DONE
                        </div>
                    </div>
                    <div className="w-[72px] h-0.5 bg-gray-500"></div>
                </div>

                <h2 className="max-w-[640px] font-bold text-primary-text text-[48px] tracking-[0] leading-[120%]">
                    We are creating a place where children with special needs can thrive
                </h2>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div className="relative w-full h-[421px] overflow-hidden rounded-[20px]">
                    <img className="absolute inset-0 w-full h-full object-cover" alt="Mission smile 1k" src="/assets/images/child-care.png" />
                    <div className="absolute inset-0 bg-black rounded-[20px] opacity-60"></div>
                    <div className="absolute top-20 left-12 w-[319px] h-[293px] flex flex-col">
                        <div className="w-[315px] h-[84px] font-bold text-white text-[28px] tracking-[0] leading-[150%]">
                            Mission smile 1k: Outdoor charity
                        </div>
                        <div className="self-end mr-1 w-[315px] h-[78px] mt-4 text-white text-base tracking-[0] leading-[160%]">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse varius enim in eros.
                        </div>
                        <button className="w-[146px] h-auto mt-[64px] px-8 py-4 bg-white hover:opacity-90 rounded backdrop-blur-2xl backdrop-brightness-[100%]">
                            <span className="font-medium text-primary-text text-base text-right tracking-[0] leading-[normal] whitespace-nowrap">
                                Learn more
                            </span>
                        </button>
                    </div>
                </div>

                <div className="relative w-full h-[421px] overflow-hidden rounded-[20px]">
                    <img className="absolute inset-0 w-full h-full object-cover" alt="Weekly excursions" src="/assets/images/about-us.png" />
                    <div className="absolute inset-0 bg-black rounded-[20px] opacity-60"></div>
                    <div className="absolute top-20 left-12 w-[319px] h-[293px] flex flex-col">
                        <div className="w-[315px] h-[42px] font-bold text-white text-[28px] tracking-[0] leading-[150%]">
                            Weekly excursions
                        </div>
                        <div className="self-end mr-1 w-[315px] h-[78px] mt-4 text-white text-base tracking-[0] leading-[160%]">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse varius enim in eros.
                        </div>
                        <button className="w-[146px] h-auto mt-[106px] px-8 py-4 bg-white hover:opacity-90 rounded backdrop-blur-2xl backdrop-brightness-[100%]">
                            <span className="font-medium text-primary-text text-base text-right tracking-[0] leading-[normal] whitespace-nowrap">
                                Learn more
                            </span>
                        </button>
                    </div>
                </div>

                <div className="relative w-full h-[421px] overflow-hidden rounded-[20px]">
                    <img className="absolute inset-0 w-full h-full object-cover" alt="Monthly public awareness" src="/assets/images/medical-camp.png" />
                    <div className="absolute inset-0 bg-black rounded-[20px] opacity-60"></div>
                    <div className="absolute top-20 left-12 w-[319px] h-[293px] flex flex-col">
                        <div className="w-[315px] h-[84px] font-bold text-white text-[28px] tracking-[0] leading-[150%]">
                            Monthly public awareness
                        </div>
                        <div className="self-end mr-1 w-[315px] h-[78px] mt-4 text-white text-base tracking-[0] leading-[160%]">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse varius enim in eros.
                        </div>
                        <button className="w-[146px] h-auto mt-[64px] px-8 py-4 bg-white hover:opacity-90 rounded backdrop-blur-2xl backdrop-brightness-[100%]">
                            <span className="font-medium text-primary-text text-base text-right tracking-[0] leading-[normal] whitespace-nowrap">
                                Learn more
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {/* <!-- Statistics Section --> */}
    <section className="relative w-full bg-black py-[85px] px-[110px]">
        <div className="max-w-[1500px] mx-auto">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                <div className="flex flex-col gap-8 max-w-[633px]">
                    <div className="flex flex-col gap-4">
                        <h2 className="font-bold text-white text-[48px] tracking-[0] leading-[120%]">
                            How we spend your donations and where it goes
                        </h2>

                        <p className="opacity-60 text-white text-base tracking-[0] leading-[160%]">
                            We understand that when you make a donation, you want to know
                            exactly where your money is going and we pledge to be
                            transparent.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div className="flex items-center gap-2">
                            <div className="w-4 h-4 rounded bg-green-secondary flex-shrink-0"></div>
                            <span className="font-medium text-white text-base tracking-[0] leading-[25.6px]">
                                40% child care home
                            </span>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="w-4 h-4 rounded bg-[#ac94f1] flex-shrink-0"></div>
                            <span className="font-medium text-white text-base tracking-[0] leading-[25.6px]">
                                35% cleanliness program
                            </span>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="w-4 h-4 rounded bg-[#fff0c9] flex-shrink-0"></div>
                            <span className="font-medium text-white text-base tracking-[0] leading-[25.6px]">
                                10% helping people
                            </span>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="w-4 h-4 rounded bg-[#f9cf64] flex-shrink-0"></div>
                            <span className="font-medium text-white text-base tracking-[0] leading-[25.6px]">
                                10% excursions
                            </span>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="w-4 h-4 rounded bg-[#f38ebf] flex-shrink-0"></div>
                            <span className="font-medium text-white text-base tracking-[0] leading-[25.6px]">
                                5% feeding the poor
                            </span>
                        </div>
                    </div>
                </div>

                <div className="relative w-full max-w-[375px] h-[556px] mx-auto lg:ml-auto lg:mr-0">
                    <img className="absolute top-[181px] left-[188px] w-[188px] h-[352px]" alt="Ellipse" src="/ellipse-1.svg" />
                    <img className="absolute top-[316px] left-0 w-[278px] h-60" alt="Ellipse" src="/ellipse-3.svg" />
                    <img className="absolute top-[227px] left-[7px] w-[115px] h-[114px]" alt="Ellipse" src="/ellipse-4.svg" />
                    <img className="absolute top-[184px] left-[63px] w-[107px] h-[111px]" alt="Ellipse" src="/ellipse.svg" />
                    <img className="absolute top-[181px] left-[153px] w-[39px] h-[91px]" alt="Ellipse" src="/ellipse-2.svg" />

                    <div className="absolute top-0 left-[33.07%] flex items-center">
                        <span className="font-bold text-white text-2xl tracking-[0] leading-[44px] whitespace-nowrap">
                            ACCS
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {/* <!-- Call to Action Section --> */}
    <section className="relative py-24 w-full flex items-center justify-center bg-[url(/assets/images/Donor-Focused-Version.png)] bg-cover bg-center">
        <div className="inset-0 bg-opacity-50 bg-black absolute"></div>
        <div className="flex flex-col items-center gap-8 px-4 max-w-5xl relative">
            <h2 className="font-bold text-white text-[48px] text-center tracking-[0] leading-[120%]">
                Your generosity makes it possible for us to build a healing space that delivers specialized medical care and support to children with special needs.
            </h2>

            <div className="flex gap-8">
                <button className="h-auto px-8 py-4 bg-yellow-500 hover:opacity-90 text-[#000000] font-medium text-base rounded backdrop-blur-2xl backdrop-brightness-[100%]">
                    Join as a volunteer
                </button>

                <button className="h-auto px-8 py-4 bg-white hover:opacity-90 text-primary-text font-medium text-base rounded backdrop-blur-2xl backdrop-brightness-[100%]">
                    Donate
                </button>
            </div>
        </div>
    </section>

    {/* <!-- Events Section --> */}
    <section id="media" className="w-full px-[110px] py-12">
        <div className="max-w-[1280px] mx-auto">
            <div className="flex items-center gap-4 mb-10">
                <h2 className="font-medium text-primary-text text-[40px] tracking-[0] leading-[56px] whitespace-nowrap">
                    Our Events
                </h2>
                <div className="flex-1 h-px bg-primary-text"></div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="bg-yellow-500 rounded-[20px] p-10">
                    <div className="flex items-start gap-5">
                        <div className="flex flex-col">
                            <div className="font-medium text-primary-text text-5xl tracking-[0] leading-[57.6px] whitespace-nowrap">
                                13
                            </div>
                            <div className="font-medium text-primary-text text-base tracking-[2.00px] leading-[normal] whitespace-nowrap">
                                APR
                            </div>
                        </div>

                        <div className="relative flex-1">
                            <div className="absolute -top-[85px] left-[85px] w-[21px] h-[190px] flex flex-col gap-24 rotate-90">
                                <img className="ml-[-14.0px] w-11 h-0.5 mt-[21.0px] -rotate-90" alt="Line" src="/line.svg" />
                                <div className="ml-[-52.0px] w-[123px] h-[19px] -rotate-90 font-medium text-primary-text text-base tracking-[2.00px] leading-[normal] whitespace-nowrap">
                                    NEXT EVENTS
                                </div>
                            </div>
                            <div className="mt-[27px] font-bold text-primary-text text-[28px] tracking-[0] leading-[150%]">
                                A day with our wonderful children
                            </div>
                        </div>

                        <img className="w-14 h-14 mt-8 flex-shrink-0" alt="Arrow button" src="/assets/images/arrow-button.png" />
                    </div>
                </div>

                <div className="bg-yellow-500 rounded-[20px] p-10">
                    <div className="flex items-start gap-5">
                        <div className="flex flex-col">
                            <div className="font-medium text-primary-text text-5xl tracking-[0] leading-[57.6px] whitespace-nowrap">
                                25
                            </div>
                            <div className="font-medium text-primary-text text-base tracking-[2.00px] leading-[normal] whitespace-nowrap">
                                APR
                            </div>
                        </div>

                        <div className="relative flex-1">
                            <div className="absolute -top-[85px] left-[85px] w-[21px] h-[190px] flex flex-col gap-24 rotate-90">
                                <img className="ml-[-14.0px] w-11 h-0.5 mt-[21.0px] -rotate-90" alt="Line" src="/line.svg" />
                                <div className="ml-[-52.0px] w-[123px] h-[19px] -rotate-90 font-medium text-primary-text text-base tracking-[2.00px] leading-[normal] whitespace-nowrap">
                                    NEXT EVENTS
                                </div>
                            </div>
                            <div className="mt-[27px] font-bold text-primary-text text-[28px] tracking-[0] leading-[150%]">
                                Seminar: Caring for children with autism
                            </div>
                        </div>

                        <img className="w-14 h-14 mt-8 flex-shrink-0" alt="Arrow button" src="/assets/images/arrow-button.png" />
                    </div>
                </div>
            </div>
        </div>
    </section>
    </>
  );
}
