"use client";
import { useRef, useState } from "react";
import Image from "next/image";

export default function Membership() {
  const formRef = useRef<any>(null);
  const [selectedPlan, setSelectedPlan] = useState("");

  const plans = [
    {
      title: "Basic",
      price: 9,
      desc: "Perfect for beginners",
      features: [
        "Access to standard features",
        "Email support",
        "Monthly updates",
      ],
    },
    // {
    //   title: "Premium",
    //   price: 19,
    //   desc: "Best value for professionals",
    //   features: [
    //     "All Basic features",
    //     "Priority support",
    //     "Bonus resources",
    //     "Weekly updates",
    //   ],
    // },
    // {
    //   title: "Enterprise",
    //   price: 49,
    //   desc: "Complete solution for teams",
    //   features: [
    //     "All Premium features",
    //     "Dedicated manager",
    //     "Custom integrations",
    //     "Unlimited resources",
    //   ],
    // },
  ];

  const handleChoosePlan = (planName: any) => {
    setSelectedPlan(planName);

    // Smooth scroll to form section
    formRef.current?.scrollIntoView({ behavior: "smooth" });
  };

  return (
    <>
      {/* ======= HERO ======= */}
      <header className="relative w-full flex flex-col gap-48 bg-[url(/assets/images/banner-img.png)] bg-cover bg-center py-32 mt-20">
        <div className="absolute inset-0 bg-black opacity-60"></div>

        <div className="container mx-auto px-[110px] relative z-[1]">
          <h1 className="font-bold text-white text-[64px] leading-[76px]">
            Membership
          </h1>
          <p className="text-lg text-white max-w-[520px] mt-6">
            Become a member today and enjoy exclusive benefits.
          </p>
        </div>
      </header>

      {/* ======= MEMBERSHIP PLANS ======= */}
      <section className="w-full py-20 px-[110px] bg-[#f8f8f8]">
        <h2 className="text-4xl font-bold mb-12 text-center">Choose Your Plan</h2>

        <div className="grid lg:grid-cols-3 gap-10">
          {plans.map((plan, index) => (
            <div
              key={index}
              className="
                group bg-white p-8 rounded-xl shadow transition 
                hover:shadow-xl hover:border-2 hover:border-yellow-500 
                border-2 border-transparent
              "
            >
              <h3 className="text-2xl font-semibold mb-3">{plan.title}</h3>
              <p className="text-gray-600 mb-6">{plan.desc}</p>

              <h4 className="text-4xl font-bold mb-4">
                ${plan.price}
                <span className="text-lg text-gray-600 font-medium">/month</span>
              </h4>

              <ul className="space-y-3 mb-6 text-gray-700">
                {plan.features.map((feature, i) => (
                  <li key={i}>✔ {feature}</li>
                ))}
              </ul>

              <button
                onClick={() => handleChoosePlan(plan.title)}
                className="w-full bg-yellow-500 py-3 rounded-lg font-semibold hover:opacity-90"
              >
                Choose Plan
              </button>
            </div>
          ))}
        </div>
      </section>

      {/* ======= MEMBERSHIP FORM ======= */}
      <section ref={formRef} className="w-full py-20 px-[110px] scroll-mt-40">
        <h2 className="text-4xl font-bold mb-12 text-center">Membership Form</h2>

        <div className="max-w-4xl mx-auto bg-white shadow-lg p-10 rounded-xl">
          <form className="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div>
              <label className="block mb-2 text-gray-600">Full Name</label>
              <input
                type="text"
                className="w-full p-3 rounded-lg border border-gray-300 focus:border-yellow-500"
                placeholder="Your full name"
              />
            </div>

            <div>
              <label className="block mb-2 text-gray-600">Email</label>
              <input
                type="email"
                className="w-full p-3 rounded-lg border border-gray-300 focus:border-yellow-500"
                placeholder="Your email"
              />
            </div>

            <div>
              <label className="block mb-2 text-gray-600">Phone Number</label>
              <input
                type="text"
                className="w-full p-3 rounded-lg border border-gray-300 focus:border-yellow-500"
                placeholder="Phone number"
              />
            </div>

            {/* Auto-select plan */}
            <div>
              <label className="block mb-2 text-gray-600">Selected Plan</label>
              <select
                value={selectedPlan}
                onChange={(e) => setSelectedPlan(e.target.value)}
                className="w-full p-3 rounded-lg border border-gray-300 focus:border-yellow-500"
              >
                <option value="">Choose a plan</option>
                <option value="Basic">Basic - $9/month</option>
                <option value="Premium">Premium - $19/month</option>
                <option value="Enterprise">Enterprise - $49/month</option>
              </select>
            </div>

            <div className="md:col-span-2">
              <label className="block mb-2 text-gray-600">Address</label>
              <textarea
                rows={4}
                className="w-full p-3 rounded-lg border border-gray-300 focus:border-yellow-500"
                placeholder="Your full address..."
              ></textarea>
            </div>
          </form>

          <div className="mt-8 text-center">
            <button className="px-10 py-4 bg-yellow-500 rounded-lg text-black font-semibold hover:opacity-90">
              Submit Membership
            </button>
          </div>
        </div>
      </section>
    </>
  );
}