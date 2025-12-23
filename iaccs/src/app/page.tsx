import Image from "next/image";
import Link from "next/link";

export default function Home() {
  return (
   
    <>
    {/* <!-- Header Section --> */}
    <header className="relative w-full flex flex-col gap-48 bg-transparent bg-[url(/assets/images/banner-img.png)] bg-cover bg-center py-32 mt-20 bg-gray-500">
      <div className="absolute inset-0 bg-black opacity-50"></div>
        <div className="container mx-auto xl:px-[110px] md:px-20 px-5 relative z-[1]">
            <div className="w-full max-w-[642px] flex flex-col gap-12">
                <div className="text-white">
                    <h1 className="w-full max-w-[640px] font-bold text-white xl:text-[64px] md:text-4xl text-3xl tracking-[0] xl:leading-[76.8px]">Welcome to ACCS The Association for Critical Care Sciences</h1>
                <p>R ECOG N I T IO N . S TA N DARDS . E XCE L L E N CE .</p>
                </div>
                <p className="text-lg text-white">ACCS is dedicated to advancing clinical excellence, promoting education, and strengthening the future workforce in Critical Care Science. Together, we work for recognition, standardization, and growth of our profession</p>

                <div className="flex gap-6">
                    <button className="h-auto px-8 py-4 bg-white text-primary-text hover:bg-opacity-90 rounded backdrop-blur-2xl backdrop-brightness-[100%]">
                        <Link href="/membership" className="font-medium text-base text-right tracking-[0] leading-[normal] whitespace-nowrap">
                            Volunteer Today
                        </Link>
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
    <section id="about" className="w-full flex flex-col gap-16 py-16 xl:px-[110px] md:px-20 px-5">
        <div className="w-full flex flex-wrap gap-[82px]">
            <div className="flex-1 flex flex-col gap-8 relative">
                {/* <div className="flex items-center gap-8">
                    <div className="flex items-center gap-4">
                        <div className="w-[72px] h-0.5 bg-primary-text"></div>
                        <div className="font-bold text-primary-text text-base tracking-[2.00px] leading-[normal] whitespace-nowrap">
                            
                        </div>
                    </div>
                </div> */}

                <h2 className="font-bold text-primary-text xl:text-2xl md:text-xl text-lg tracking-[0] xl:leading-[120%]">MISSION & VISION</h2>

                <p className="text-secondary-text text-base tracking-[0] leading-[160%]">The Association for Critical Care Sciences (ACCS) is a community-led initiative formed to represent, support, and advance the field of Critical Care Science in India. We work towards unifying students, graduates, educators, and professionals to strengthen recognition, create academic opportunities, and uphold high standards in clinical practice.</p>

                <h3 className="font-bold">MISSION & VISION</h3>

                <p>To empower Critical Care professionals through education, advocacy, collaboration, and skill development, ensuring excellence in patient care across Intensive Care settings.</p>

                <p>A future where Critical Care Science is nationally recognized, standardized, and valued as an essential healthcare specialty supported by strong academic pathways, ethical practice, and professional dignity.</p>

                <div>
                    <Link href="/about-us" className="bg-yellow-500 hover:opacity-90 text-black font-medium text-base px-8 py-4 rounded h-auto">
                        Learn more
                    </Link>
                </div>
            </div>

            <div className="md:w-[480px] md:flex-shrink-0">
                <img className="w-full h-auto" alt="Video" src="/assets/images/about-us.png" />
            </div>
        </div>
    </section>

    <section id="services" className="relative w-full bg-yellow-300 xl:py-[147px] md:py-20 py-10 xl:mt-[74px]">
        <div className="max-w-[1500px] mx-auto xl:px-[206px] md:px-10 px-5">
            <div className="flex flex-wrap md:flex-nowrap gap-10">
                <div className="font-bold text-primary-text xl:text-[48px] md:text-3xl text-2xl tracking-[0] xl:leading-[120%]">200+ Student Members</div>
                <div className="font-bold text-primary-text xl:text-[48px] md:text-3xl text-2xl tracking-[0] xl:leading-[120%]">200+ Professional Members</div>
            </div>
        </div>
    </section>

    {/* <!-- Projects Section --> */}
    <section className="relative w-full xl:px-[110px] md:px-20 px-5 py-24">
        <div className="max-w-[1280px] mx-auto">
            <div className="flex flex-wrap items-start gap-8 xl:mb-[99px] md:mb-10 mb-5">
                <div className="flex items-center gap-6">
                    <div className="flex flex-col items-center gap-4">
                        <div className="font-bold text-primary-text text-base tracking-[2.00px] leading-[normal] whitespace-nowrap xl:-rotate-90 origin-center uppercase">
                            Building the Future
                        </div>
                    </div>
                    <div className="w-[72px] h-0.5 bg-gray-500"></div>
                </div>

                <h2 className="max-w-[640px] font-bold text-primary-text xl:text-[48px] md:text-3xl text-2xl !leading-none">Building the Future of Critical Care Professionals in India</h2>
                <p className="md:pl-80 ">Empowering students, trainees, and professionals through organized efforts, education, and advocacy.</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div className="relative w-full h-[421px] overflow-hidden rounded-[20px]">
                    <img className="absolute inset-0 w-full h-full object-cover" alt="Mission smile 1k" src="/assets/images/child-care.png" />
                    <div className="absolute inset-0 bg-black rounded-[20px] opacity-60"></div>
                    <div className="absolute top-20 left-12 w-[319px] h-[293px] flex flex-col">
                        <h3 className="w-[315px] h-[84px] font-bold text-white text-[28px] tracking-[0] leading-[150%]">
                            Advocacy for Recognition
                        </h3>
                        <div className="self-end mr-1 w-[315px] h-[78px] mt-4 text-white text-base tracking-[0] leading-[160%]">
                            Working toward the official recognition of Critical Care Science under national healthcare frameworks. We collaborate with policymakers, institutions, and stakeholders to secure professional identity and rights
                        </div>
                        <button className="w-[146px] h-auto mt-[64px] px-8 py-4 bg-white hover:opacity-90 rounded backdrop-blur-2xl backdrop-brightness-[100%]">
                            <Link href="/about-us" className="font-medium text-primary-text text-base text-right tracking-[0] leading-[normal] whitespace-nowrap">
                                Learn more
                            </Link>
                        </button>
                    </div>
                </div>

                <div className="relative w-full h-[421px] overflow-hidden rounded-[20px]">
                    <img className="absolute inset-0 w-full h-full object-cover" alt="Weekly excursions" src="/assets/images/about-us.png" />
                    <div className="absolute inset-0 bg-black rounded-[20px] opacity-60"></div>
                    <div className="absolute top-20 left-12 w-[319px] h-[293px] flex flex-col">
                        <div className="w-[315px] font-bold text-white text-[28px] tracking-[0] leading-[150%]">
                            Training & Skill Development
                        </div>
                        <div className="self-end mr-1 w-[315px] h-[78px] mt-4 text-white text-base tracking-[0] leading-[160%]">
                            Helping students and professionals enhance their knowledge and hands-on ICU
                        </div>
                        <button className="w-[146px] h-auto mt-[106px] px-8 py-4 bg-white hover:opacity-90 rounded backdrop-blur-2xl backdrop-brightness-[100%]">
                            <Link href="/about-us" className="font-medium text-primary-text text-base text-right tracking-[0] leading-[normal] whitespace-nowrap">
                                Learn more
                            </Link>
                        </button>
                    </div>
                </div>

                <div className="relative w-full h-[421px] overflow-hidden rounded-[20px]">
                    <img className="absolute inset-0 w-full h-full object-cover" alt="Monthly public awareness" src="/assets/images/medical-camp.png" />
                    <div className="absolute inset-0 bg-black rounded-[20px] opacity-60"></div>
                    <div className="absolute top-20 left-12 w-[319px] h-[293px] flex flex-col">
                        <div className="w-[315px] h-[84px] font-bold text-white text-[28px] tracking-[0] leading-[150%]">
                            Academic Support & Study Resources
                        </div>
                        <div className="self-end mr-1 w-[315px] h-[78px] mt-4 text-white text-base tracking-[0] leading-[160%]">
                            Providing structured learning materials, mentorship, and access to essential educational resources for students and practicing professionals in critical care domains
                        </div>
                        <button className="w-[146px] h-auto mt-[64px] px-8 py-4 bg-white hover:opacity-90 rounded backdrop-blur-2xl backdrop-brightness-[100%]">
                            <Link href="/about-us" className="font-medium text-primary-text text-base text-right tracking-[0] leading-[normal] whitespace-nowrap">
                                Learn more
                            </Link>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {/* <!-- Statistics Section --> */}
    <section className="relative w-full bg-black xl:py-[85px] py-10 xl:px-[110px] md:px-20 px-5">
        <div className="max-w-[1500px] mx-auto">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                <div className="flex flex-col gap-8 max-w-[633px]">
                    <div className="flex flex-col gap-4">
                        <h2 className="font-bold text-white xl:text-[48px] md:text-3xl text-2xl tracking-[0] !leading-tight">
                            Join us to make it possible to create a better place for Critical Care Professionals.
                        </h2>

                        <button className="w-[146px] h-auto px-8 py-4 bg-white hover:opacity-90 rounded backdrop-blur-2xl backdrop-brightness-[100%]">
                            <Link href="/membership" className="font-medium text-primary-text text-base text-right tracking-[0] leading-[normal] whitespace-nowrap">
                                Join Us
                            </Link>
                        </button>
                    </div>                    
                </div>

                <div className="w-full">
                    <div className="relative max-w-[480px]">
                        <img className="w-full h-auto" alt="Video" src="/assets/images/about-us.png" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    {/* <!-- Call to Action Section --> */}
    {/* <section className="relative py-24 w-full flex items-center justify-center bg-[url(/assets/images/Donor-Focused-Version.png)] bg-cover bg-center">
        <div className="inset-0 bg-opacity-50 bg-black absolute"></div>
        <div className="flex flex-col items-center gap-8 px-4 max-w-5xl relative">
            <h2 className="font-bold text-white xl:text-[48px] md:text-3xl text-2xl !leading-none">
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
    </section> */}

    {/* <!-- Events Section --> */}
    <section id="media" className="w-full xl:px-[110px] md:px-20 px-5 py-12">
        <div className="max-w-[1280px] mx-auto">
            <div className="flex items-center gap-4 mb-10">
                <h2 className="font-bold xl:text-[48px] md:text-3xl text-2xl !leading-none">
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
                                Coming Soon...
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
                                Coming Soon...
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
