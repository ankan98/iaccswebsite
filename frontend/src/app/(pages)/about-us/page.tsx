export default function About() {
  return (
    <div className="bg-white mt-[40px]">
      <section className="w-full bg-white flex flex-col py-16 xl:px-[110px] md:px-20 px-5">
        <h1 className="w-full font-bold text-gray-900 xl:text-[48px] md:text-3xl text-2xl !leading-tight  mb-[20px] " style={{ fontFamily: "Georgia, serif" }} >About Us</h1>
        <div className="w-full">
          <div className="flex-1 flex flex-col gap-8 relative">
            <h2 className="font-bold text-gray-900 xl:text-[30px] md:text-3xl text-2xl !leading-tight"  style={{color:'#1a4075',}} >
              Delivering Free Healthcare to Underserved Communities Worldwide
            </h2>

            <div className="space-y-4 text-gray-700 leading-relaxed">
              <p>
                The Association for Critical Care Sciences (ACCS) was founded to
                represent, strengthen, and advance the discipline of Critical
                Care Sciences in India.
              </p>

              <p>
                With a rapidly evolving healthcare landscape and an increasing
                demand for advanced critical care professionals, our association
                serves as a unified platform for students, educators,
                researchers, healthcare institutions, and practicing
                professionals working in critical and emergency care
                environments.
              </p>

              <p>
                Critical Care Sciences has been a part of formal healthcare
                education in India for over two decades through Diploma,
                Bachelor&apos;s, and Master&apos;s level programs offered by
                recognized universities and medical institutions. Despite the
                significant clinical contribution of Critical Care
                professionals—especially in ICUs, emergency services, trauma
                units, ventilatory management, invasive monitoring, and
                life-support systems—the discipline still lacks standardized
                national recognition, legal identity, and regulatory framework.
                This gap has resulted in challenges related to employment,
                professional designation, licensure, and academic growth.
              </p>

              <p>
                ACCS was formed with the aim of addressing these challenges and
                enabling systemic reform. We advocate for national recognition
                under the NCAHP Act, standardized curriculum and training
                protocols, formal registration pathways, and defined career
                structures aligned with global medical education norms.
              </p>

              <p>
                Our mission extends beyond representation—we seek to build a
                professional ecosystem that encourages excellence in clinical
                skills, research, ethics, safety, leadership, and
                patient-centered care.
              </p>

              <p>
                As an association, we work to foster collaboration between
                healthcare delivery systems, universities, government
                authorities, regulatory bodies, and industry stakeholders. We
                actively promote continuing medical education, skill development
                programs, workshops, conferences, clinical exposure, and
                research initiatives to strengthen competency and enhance the
                quality of critical care services across India.
              </p>

              <p>
                At our core, we believe that recognition is not only a matter of
                identity—it is a matter of patient safety, system
                accountability, and national healthcare progress. Critical Care
                professionals are often the first line of response when a
                patient&apos;s life hangs in balance. Their expertise is
                indispensable to modern health systems, and their role deserves
                the regulatory foundation, respect, and structure that aligns
                with their contribution.
              </p>

              <p>
                ACCS stands as a voice, a movement, and a commitment—dedicated
                to advancing the future of Critical Care Sciences and ensuring
                that every professional in this field receives the dignity,
                clarity, and opportunities they rightfully deserve.
              </p>
            </div>
          </div>
        </div>
      </section>

      <section className="mb-[60px]">
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
      </section>
    </div>
  );
}
