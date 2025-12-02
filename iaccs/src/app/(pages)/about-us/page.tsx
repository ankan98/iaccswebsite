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

    <section className="w-full flex flex-col gap-16 py-16 px-[110px] bg-yellow-500">
        <div className="w-full flex gap-[82px]">
            <div className="max-w-6xl mx-auto px-6 grid md:grid-cols-2 gap-10 items-center">

                <div className="flex justify-center">
                    <img 
                        src="https://img.freepik.com/free-photo/smiling-elderly-male-near-plant_23-2148036681.jpg" 
                        alt="Chairman"
                        className="rounded-xl shadow-lg object-cover"
                    />
                </div>

                <div>
                    <p className="text-yellow-100 font-semibold uppercase tracking-wide mb-2">
                        Chairman’s Desk
                    </p>

                    <h2 className="text-4xl font-bold mb-4">
                        Message From Our Chairman
                    </h2>

                    <p className="leading-relaxed mb-6">
                        Our vision is to create a future where innovation and dedication come 
                        together to achieve excellence. We believe in empowering people, embracing 
                        new opportunities, and continuously pushing boundaries to bring meaningful 
                        change to society.
                    </p>

                    <div>
                        <h3 className="text-xl font-semibold">Mr. John Anderson</h3>
                        <p className="text-yellow-900">Chairman & Founder</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section className="w-full flex flex-col gap-16 py-16 px-[110px]">
        <div className="w-full flex gap-[82px]">
            <div className="flex-1 flex flex-col gap-8 relative">
                <div className="flex items-center gap-8">
                    <div className="flex items-center gap-4">
                        <div className="w-[72px] h-0.5 bg-primary-text"></div>
                        <div className="font-bold text-primary-text text-base tracking-[2.00px] leading-[normal] whitespace-nowrap">
                            Meet Our Creative Team
                        </div>
                    </div>
                </div>

                <h2 className="font-bold text-primary-text text-[48px] tracking-[0] leading-[120%]">Delivering Free Healthcare to Underserved Communities Worldwide</h2>

                <p className="text-secondary-text text-base tracking-[0] leading-[160%]">
                    We are a group of passionate designers, developers, and creators dedicated to building digital experiences that inspire. Our team works together with innovation and commitment to bring meaningful ideas to life.
                </p>

                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-8">

                    <div className="bg-white p-6 rounded-xl text-center">
                        <img
                        src="https://img.freepik.com/free-photo/young-handsome-man-wearing-casual-tshirt-blue-background-happy-face-smiling-with-crossed-arms-looking-camera-positive-person_839833-12963.jpg"
                        alt="team member"
                        className="w-48 h-48 mx-auto rounded-xl object-cover"
                        />
                        <h3 className="text-xl font-semibold mt-4">John Doe</h3>
                        <p className="text-gray-600">Web Developer</p>
                    </div>

                    <div className="bg-white p-6 rounded-xl text-center">
                        <img
                        src="https://img.freepik.com/free-photo/people-smiling-men-handsome-cheerful_1187-6057.jpg"
                        alt="team member"
                        className="w-48 h-48 mx-auto rounded-xl object-cover"
                        />
                        <h3 className="text-xl font-semibold mt-4">Sarah Smith</h3>
                        <p className="text-gray-600">UI/UX Designer</p>
                    </div>

                    <div className="bg-white p-6 rounded-xl text-center">
                        <img
                        src="https://img.freepik.com/free-photo/front-view-lovely-smiley-woman_23-2148493038.jpg"
                        alt="team member"
                        className="w-48 h-48 mx-auto rounded-xl object-cover"
                        />
                        <h3 className="text-xl font-semibold mt-4">Marina Lee</h3>
                        <p className="text-gray-600">Project Manager</p>
                    </div>

                    <div className="bg-white p-6 rounded-xl text-center">
                        <img
                        src="https://img.freepik.com/free-photo/handsome-bearded-businessman-rubbing-hands-having-deal_176420-18778.jpg"
                        alt="team member"
                        className="w-48 h-48 mx-auto rounded-xl object-cover"
                        />
                        <h3 className="text-xl font-semibold mt-4">Shone Lee</h3>
                        <p className="text-gray-600">Project Manager</p>
                    </div>

                </div>
            </div>
        </div>
    </section>
    </>
  );
}
