import Image from "next/image";

export default function Contact() {
  return (
   
  <>
  <header className="relative w-full flex flex-col gap-48 bg-transparent bg-[url(/assets/images/banner-img.png)] bg-cover bg-center py-32 mt-20 bg-gray-500">
      <div className="absolute inset-0 bg-black opacity-50"></div>
        <div className="container mx-auto px-[110px] relative z-[1]">
            <div className="w-full max-w-[642px] flex flex-col gap-12">
                <h1 className="w-full max-w-[640px] font-bold text-white text-[64px] tracking-[0] leading-[76.8px]">Contact Us</h1>
                <p className="text-lg text-white">Have any questions or want to work with us? Feel free to reach out anytime.</p>
            </div>
        </div>
    </header>

    <section className="w-full flex flex-col gap-16 py-16 px-[110px]">
        <div className="w-full gap-[82px]">
          
          <div className="flex flex-wrap -mx-4">

          <div className="lg:w-1/2 w-full px-4">
            <div className="">
              <h3 className="text-2xl font-semibold mb-6">Send Us a Message</h3>

              <form>
                <div className="mb-4">
                  <label className="block text-gray-600 mb-1">Your Name</label>
                  <input 
                    type="text" 
                    className="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-0 focus:border-yellow-500 focus:ring-yellow-500"
                    placeholder="Enter your name" />
                </div>

                <div className="mb-4">
                  <label className="block text-gray-600 mb-1">Your Email</label>
                  <input 
                    type="email" 
                    className="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-0 focus:border-yellow-500 focus:ring-yellow-500"
                    placeholder="Enter your email" />
                </div>

                <div className="mb-4">
                  <label className="block text-gray-600 mb-1">Subject</label>
                  <input 
                    type="text" 
                    className="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-0 focus:border-yellow-500 focus:ring-yellow-500"
                    placeholder="Enter subject" />
                </div>

                <div className="mb-4">
                  <label className="block text-gray-600 mb-1">Message</label>
                  <textarea rows={5}
                    className="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-0 focus:border-yellow-500 focus:ring-yellow-500"
                    placeholder="Write your message"></textarea>
                </div>

                <button className="h-auto px-8 py-4 bg-yellow-500 hover:opacity-90 text-[#000000] font-medium text-base rounded backdrop-blur-2xl backdrop-brightness-[100%]">
                  Send Message
                </button>
              </form>
            </div>
          </div>

          <div className="lg:w-1/2 w-full px-4">
            <div className="space-y-8">

              <div className="">
                <h3 className="text-2xl font-semibold mb-4">Contact Information</h3>

                <div className="space-y-4 text-gray-700">
                  <p>
                    <strong>Address:</strong>  
                    123 Business Street, Dhaka, Bangladesh
                  </p>
                  <p>
                    <strong>Phone:</strong>  
                    +880 1234 567 890
                  </p>
                  <p>
                    <strong>Email:</strong>  
                    contact@yourcompany.com
                  </p>
                </div>
              </div>

              <div className="">
                <h3 className="text-lg font-semibold mb-3">Find Us On Map</h3>
                <div className="w-full h-64 bg-gray-300 rounded-lg flex items-center justify-center">
                  <span className="text-gray-600">Google Map Placeholder</span>
                </div>
              </div>

            </div>
          </div>

          </div>
        </div>
    </section>
  </>
  );
}
