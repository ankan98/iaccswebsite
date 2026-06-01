import Link from "next/link";
import MemberStats from "./components/member-stats";
import DocumentsList from "./components/documents-list";

export default function Home() {
  return (
    <div className="bg-white">
      {/* <!-- Header Section --> */}
      <header className="relative w-full flex flex-col bg-[url(/assets/images/img189.jpg)] bg-cover bg-center py-20 md:py-24 lg:py-32 mt-20">
        {/* Overlay */}
        <div className="absolute inset-0 bg-black/50"></div>

        <div className="mx-auto px-4 sm:px-6 md:px-12 lg:px-[110px] relative z-[1]">
          <div className="w-full flex flex-col gap-6 md:gap-8 max-w-[900px]">
            {/* Heading */}
            <div className="text-white">
              <h1
                className="font-bold text-white text-2xl sm:text-3xl md:text-4xl lg:text-[45px] leading-snug lg:leading-[64px]"
                style={{
                  fontFamily: "'Playfair Display', serif",
                  textShadow: "1px 4px 1px rgb(0,0,0)",
                }}
              >
                Welcome to ACCS The Association for Critical Care Sciences
              </h1>

              <p
                className="mt-4 text-white/80 text-sm sm:text-base md:text-lg"
                style={{
                  fontFamily: '"Times New Roman", Times, serif',
                }}
              >
                RECOGNITION . STANDARDS . EXCELLENCE .
              </p>
            </div>

            {/* Description */}
            <p
              className="text-sm sm:text-base md:text-lg text-white/90 leading-relaxed"
              style={{
                fontFamily: '"Times New Roman", Times, serif',
              }}
            >
              ACCS is dedicated to advancing clinical excellence, promoting
              education, and strengthening the future workforce in Critical Care
              Science. Together, we work for recognition, standardization, and
              growth of our profession.
            </p>

            {/* Button */}
            <div className="flex flex-wrap gap-4">
              <Link
                href="/membership"
                className="inline-block px-6 md:px-8 py-2 md:py-3 text-gray-900
          rounded-full border-2 border-solid border-black
          hover:opacity-90 transition text-sm md:text-base"
                style={{
                  backgroundColor: "#38b6ff",
                  fontFamily: '"Times New Roman", Times, serif',
                  fontWeight: "bold",
                }}
              >
                JOIN US TODAY
              </Link>
            </div>
          </div>
        </div>
      </header>

      {/* <!-- About Us Section --> */}
      <section
        id="about"
        className="w-full bg-white flex flex-col gap-10 md:gap-14 py-12 md:py-16 px-5 md:px-12 lg:px-20 xl:px-[110px]"
      >
        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
          {/* Vision Card */}
          <div className="border-2 border-solid border-gray-500 rounded-2xl bg-white overflow-hidden">
            <img
              src="/assets/images/img222.jpg"
              alt="Vision"
              className="w-full h-48 sm:h-56 md:h-64 object-cover"
            />

            <div className="p-5 md:p-6 text-center">
              <h2 className="inline-block px-5 md:px-6 py-2 text-xl md:text-2xl lg:text-3xl font-bold border-2 border-solid border-black rounded-full font-serif">
                VISION
              </h2>

              <p className="mt-4 text-gray-700 leading-relaxed text-sm md:text-base text-justify md:text-justify">
                The Association for Critical Care Sciences (ACCS) is a
                community-led initiative formed to represent, support, and
                advance the field of Critical Care Technology/Science in India.
                We work towards unifying students, graduates, educators, and
                professionals to strengthen recognition, create academic
                opportunities, and uphold high standards in clinical practice.
              </p>
            </div>
          </div>

          {/* Mission Card */}
          <div className="border-2 border-solid border-gray-500 rounded-2xl bg-white overflow-hidden">
            <img
              src="/assets/images/img227.jpg"
              alt="Mission"
              className="w-full h-48 sm:h-56 md:h-64 object-cover"
            />

            <div className="p-5 md:p-6 text-center">
              <h2 className="inline-block px-5 md:px-6 py-2 text-xl md:text-2xl lg:text-3xl font-bold border-2 border-solid border-black rounded-full font-serif">
                MISSION
              </h2>

              <p className="mt-4 text-gray-700 leading-relaxed text-sm md:text-base text-justify">
                To empower Critical Care Technology professionals through
                education, advocacy, collaboration, and skill development,
                ensuring excellence in patient care across Intensive Care
                settings. A future where Critical Care Technology/Science is
                nationally recognized and valued as an essential healthcare
                specialty supported by strong academic pathways, ethical
                practice, and professional dignity.
              </p>
            </div>
          </div>
        </div>
      </section>
      <section>
        <div className="relative group px-4 sm:px-6 lg:px-[110px]">
          {/* Title */}
          <h2 className="text-center text-2xl md:text-3xl lg:text-4xl font-semibold text-gray-800 mb-10">
            Our Governing Members
          </h2>

          {/* Slider Container */}
          <div className="relative w-full mx-auto">
            {/* Left Arrow */}
            <button className="hidden lg:block absolute left-0 top-1/2 -translate-y-1/2 bg-black/40 text-white p-4 opacity-0 group-hover:opacity-100 transition">
              ◀
            </button>

            {/* Members */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6 justify-items-center">
              {/* Card 1 */}
              <div className="bg-white rounded-xl shadow p-6 text-center w-full max-w-[260px]">
                <img
                  src="/assets/images/bapan-sarkar.jpg"
                  alt="member"
                  className="w-28 h-28 md:w-32 md:h-32 object-cover rounded-lg mx-auto"
                />
                <h3 className="mt-4 font-semibold text-gray-800">
                  BAPAN SARKAR
                </h3>
                <p className="text-sm text-gray-600">President</p>
                <p className="text-sm text-gray-500">M.sc CCST</p>
              </div>

              {/* Card 2 */}
              <div className="bg-white rounded-xl shadow p-6 text-center w-full max-w-[260px]">
                <img
                  src="/assets/images/atri-banerjee.jpg"
                  alt="member"
                  className="w-28 h-28 md:w-32 md:h-32 object-cover rounded-lg mx-auto"
                />
                <h3 className="mt-4 font-semibold text-gray-800">
                  ATRI BANERJEE
                </h3>
                <p className="text-sm text-gray-600">General Secretary</p>
                <p className="text-sm text-gray-500">B.sc CCT</p>
              </div>              
            </div>

            {/* Right Arrow */}
            <button className="hidden lg:block absolute right-0 top-1/2 -translate-y-1/2 bg-black/40 text-white p-4 opacity-0 group-hover:opacity-100 transition">
              ▶
            </button>
          </div>
        </div>

        {/* Button */}
        <div className="text-center mt-10 px-4">
          <Link
            href="/about-us"
            className="inline-block px-8 py-3 bg-[#38b6ff] border-2 border-solid border-black rounded-full font-semibold"
          >
            View Full list
          </Link>
        </div>

        {/* Notice */}
        <div className="w-full px-4 sm:px-6 lg:px-[110px] mb-4">
          <div className="mx-auto mt-8 border-2 border-solid border-gray-500 rounded-xl p-4 text-red-500 text-center text-sm md:text-base">
            *Due to some limitations, we are currently unable to publish the
            members list. However we assure you that it will be made available
            at a later date.
          </div>
        </div>
      </section>
      <section>
        <div className="px-4 sm:px-6 lg:px-[110px] py-10">
          <div className="border-2 border-black rounded-3xl overflow-hidden bg-gray-100">
            {/* Three Column Section */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
              {/* Announcements */}
              <div className="p-6 md:p-8 border-b md:border-b lg:border-r border-black">
                <div className="bg-green-500 text-white flex items-center gap-3 px-6 py-3 rounded-full mb-6 w-full justify-center">
                  <img src="assets/images/img24.png" alt="" width={28} />
                  <h3 className="text-lg md:text-xl font-semibold">
                    Announcements
                  </h3>
                </div>

                <DocumentsList
                  type="announcements"
                  limit={10}
                  showMoreHref="/notices-announcements#announcements"
                />

              </div>

              {/* Notices */}
              <div className="p-6 md:p-8 border-b lg:border-b-0 lg:border-r border-black">
                <div className="bg-green-500 text-white flex items-center gap-3 px-6 py-3 rounded-full mb-6 w-full justify-center">
                  <img src="assets/images/img57.png" alt="" width={22.4} />
                  <h3 className="text-lg md:text-xl font-semibold">Notices</h3>
                </div>

                <DocumentsList
                  type="notices"
                  limit={10}
                  showMoreHref="/notices-announcements#notices"
                />
              </div>

              {/* Reports */}
              <div className="p-6 md:p-8">
                <div className="bg-green-500 text-white flex items-center gap-3 px-6 py-3 rounded-full mb-6 w-full justify-center">
                  <img src="assets/images/reports.png" alt="" width={30} />
                  <h3 className="text-lg md:text-xl font-semibold">Reports</h3>
                </div>

                <DocumentsList
                  type="reports"
                  limit={10}
                  showMoreHref="/notices-announcements#reports"
                />
              </div>
            </div>

            {/* Bottom Note */}
            <div className="border-t border-black p-4 text-center text-red-500 text-sm md:text-lg">
              *Note:- If you can’t find any announcement, notices or reports
              visit the official sections.
            </div>
          </div>
        </div>
      </section>

      <section>
        <div className="w-full px-4 sm:px-6 md:px-10 lg:px-[110px]">
          {/* Title */}
          <h2 className="text-center text-xl md:text-2xl font-semibold mb-4">
            Critical Care Technology Professionals working in hospital settings
          </h2>

          {/* Image Container */}
          <div className="relative group">
            {/* Left Arrow */}
            <button className="absolute left-0 top-1/2 -translate-y-1/2 bg-black/40 text-white p-2 md:p-4 opacity-0 group-hover:opacity-100 transition">
              ◀
            </button>

            {/* Images */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 w-full overflow-hidden gap-2 md:gap-3 lg:gap-1 px-0 md:px-6 lg:px-[60px] mb-[40px]">
              <div>
                <img
                  src="assets/images/img297.jpg"
                  alt=""
                  className="w-full h-[250px] md:h-[350px] lg:h-[500px] object-cover"
                />
              </div>

              <div>
                <img
                  src="assets/images/img300.jpg"
                  alt=""
                  className="w-full h-[250px] md:h-[350px] lg:h-[500px] object-cover"
                />
              </div>

              <div>
                <img
                  src="assets/images/img303.jpg"
                  alt=""
                  className="w-full h-[250px] md:h-[350px] lg:h-[500px] object-cover"
                />
              </div>
            </div>

            {/* Right Arrow */}
            <button className="absolute right-0 top-1/2 -translate-y-1/2 bg-black/40 text-white p-2 md:p-4 opacity-0 group-hover:opacity-100 transition">
              ▶
            </button>
          </div>

          {/* Stats Section */}
          <MemberStats />
        </div>
      </section>

      <section className="px-4 sm:px-6 md:px-10 lg:px-[110px] py-10">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {/* Card 1 */}
          <div className="border border-gray-300 p-4">
            <div className="rounded-xl overflow-hidden">
              <img
                src="assets/images/img324.jpg"
                alt=""
                className="h-[200px] sm:h-[220px] md:h-[240px] lg:h-[260px] w-full object-cover rounded-xl"
              />
            </div>

            <h3
              className="font-semibold mt-4 text-[20px] md:text-[22px]"
              style={{ fontFamily: '"Times New Roman", Times, serif' }}
            >
              Advocacy for Recognition
            </h3>

            <p
              className="mt-3 text-gray-700 text-justify text-[16px] md:text-[18px]"
              style={{ fontFamily: '"Times New Roman", Times, serif' }}
            >
              Working toward the official recognition of Critical Care
              Technology/Science under national healthcare frameworks. We
              collaborate with policymakers, institutions, and stakeholders to
              secure professional identity and rights
            </p>
          </div>

          {/* Card 2 */}
          <div className="border border-gray-300 p-4">
            <div className="rounded-xl overflow-hidden">
              <img
                src="assets/images/img327.jpg"
                alt=""
                className="h-[200px] sm:h-[220px] md:h-[240px] lg:h-[260px] w-full object-cover rounded-xl"
              />
            </div>

            <h3
              className="font-semibold mt-4 text-[20px] md:text-[22px]"
              style={{ fontFamily: '"Times New Roman", Times, serif' }}
            >
              Training & Skill Development
            </h3>

            <p
              className="mt-3 text-gray-700 text-justify text-[16px] md:text-[18px]"
              style={{ fontFamily: '"Times New Roman", Times, serif' }}
            >
              Helping students and professionals enhance their knowledge and
              hands-on ICU skills through structured programs and learning
              opportunities.
            </p>
          </div>

          {/* Card 3 */}
          <div className="border border-gray-300 p-4">
            <div className="rounded-xl overflow-hidden">
              <img
                src="assets/images/img330.jpg"
                alt=""
                className="h-[200px] sm:h-[220px] md:h-[240px] lg:h-[260px] w-full object-cover rounded-xl"
              />
            </div>

            <h3
              className="font-semibold mt-4 text-[20px] md:text-[22px]"
              style={{ fontFamily: '"Times New Roman", Times, serif' }}
            >
              Academic Support & Study Resources
            </h3>

            <p
              className="mt-3 text-gray-700 text-justify text-[16px] md:text-[18px]"
              style={{ fontFamily: '"Times New Roman", Times, serif' }}
            >
              Providing structured learning materials, mentorship, and access to
              essential educational resources for students and practicing
              professionals in critical care domains.
            </p>
          </div>
        </div>
      </section>
    </div>
  );
}
