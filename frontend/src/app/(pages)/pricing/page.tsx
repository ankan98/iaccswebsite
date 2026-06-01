export default function PricingPage() {
  return (
    <div className="bg-white">
      <header className="relative mt-20 w-full bg-[url(/assets/images/banner-img.png)] bg-cover bg-center py-28">
        <div className="absolute inset-0 bg-black/60" />
        <div className="relative mx-auto max-w-6xl px-6 md:px-[110px]">
          <h1 className="text-5xl font-bold text-white">
            Membership Plans
          </h1>
          <p className="mt-3 text-lg text-white">
            Join us and be part of a vibrant community dedicated to advancing critical care.
          </p>
        </div>
      </header>
    
      <section className="w-full bg-gray-50 flex flex-col gap-16 py-16 xl:px-[110px] md:px-20 px-5">
        <div className="max-w-4xl mx-auto text-center mb-8">
          <h2 className="text-4xl font-bold text-gray-900 mb-4">Find the perfect plan for you</h2>
          <p className="text-gray-600">
            Join our community of Critical Care professionals and students.
          </p>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-5xl mx-auto">
            <div className="bg-white border border-gray-200 rounded-lg shadow-lg p-8 flex flex-col transform hover:scale-105 transition-transform duration-300">
              <h2 className="text-2xl font-semibold mb-4">Student Membership</h2>
              <p className="text-4xl font-bold mb-4">₹50 <span className="text-lg font-normal text-gray-500">/ year</span></p>
              <p className="text-gray-600 mb-6">Ideal for students enrolled in Critical Care programs.</p>
              <ul className="space-y-4 text-gray-700 leading-relaxed mb-8">
                  <li className="flex items-center"><svg className="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>Official association affiliation with ACCS</li>
                  <li className="flex items-center"><svg className="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>Recognition as a student member of a professional body</li>
                  <li className="flex items-center"><svg className="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>Access to academic resources, guidelines, and study materials</li>
                  <li className="flex items-center"><svg className="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>Participation in workshops, webinars, seminars, and skill programs</li>
                  <li className="flex items-center"><svg className="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>Opportunities for mentorship from senior Critical Care professionals</li>
                  <li className="flex items-center"><svg className="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>Early exposure to career pathways and professional ethics</li>
                  <li className="flex items-center"><svg className="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>Participation in student forums, discussions, and academic activities</li>
                  <li className="flex items-center"><svg className="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>Eligibility to apply for student coordinator and volunteer roles</li>
                  <li className="flex items-center"><svg className="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>Updates on policy, recognition, and regulatory developments</li>
              </ul>
              <a href="/membership" className="mt-auto text-center bg-blue-600 text-white font-medium py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                Choose Plan
              </a>
            </div>
            <div className="relative bg-white border border-blue-500 rounded-lg shadow-lg p-8 flex flex-col transform hover:scale-105 transition-transform duration-300">
              <div className="absolute top-0 -translate-y-1/2 bg-blue-500 text-white text-sm font-bold px-4 py-1 rounded-full">Most Popular</div>
              <h2 className="text-2xl font-semibold mb-4">Professional Membership</h2>
              <p className="text-4xl font-bold mb-4">₹100 <span className="text-lg font-normal text-gray-500">/ year</span></p>
              <p className="text-gray-600 mb-6">For practicing Critical Care professionals.</p>
              <ul className="space-y-4 text-gray-700 leading-relaxed mb-8">
                  <li className="flex items-center"><svg className="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>Official professional recognition under ACCS</li>
                  <li className="flex items-center"><svg className="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>Platform for national-level representation and advocacy.</li>
                  <li className="flex items-center"><svg className="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>Eligibility to hold posts in ACCS committees and governing bodies</li>
                  <li className="flex items-center"><svg className="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>Access to clinical guidelines, academic updates, and professional resources</li>
                  <li className="flex items-center"><svg className="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>Participation in conferences, training programs.</li>
                  <li className="flex items-center"><svg className="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>Networking with Critical Care clinicians, educators, and institutions.</li>
                  <li className="flex items-center"><svg className="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>Support in career development and professional visibility</li>
                  <li className="flex items-center"><svg className="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>Opportunity to contribute to research, publications, and academic work</li>
                  <li className="flex items-center"><svg className="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>Involvement in policy dialogue and regulatory engagement.</li>
              </ul>
              <a href="/membership" className="mt-auto text-center bg-blue-600 text-white font-medium py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                Choose Plan
              </a>
            </div>
        </div>
      </section>
    </div>
  );
}
