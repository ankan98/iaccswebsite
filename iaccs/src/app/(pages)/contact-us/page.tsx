"use client";

import { useState, useEffect } from "react";

export default function Contact() {
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    subject: "",
    message: ""
  });
  
  const [errors, setErrors] = useState({
    name: "",
    email: "",
    subject: ""
  });
  
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitMessage, setSubmitMessage] = useState({ type: "", text: "" });

  // Effect to auto-hide success message after 2 seconds
  useEffect(() => {
    if (submitMessage.type === "success" && submitMessage.text) {
      const timer = setTimeout(() => {
        setSubmitMessage({ type: "", text: "" });
      }, 2000); // 2 seconds
      
      return () => clearTimeout(timer);
    }
  }, [submitMessage]);

  const validateForm = () => {
    const newErrors = { name: "", email: "", subject: "" };
    let isValid = true;

    if (!formData.name.trim()) {
      newErrors.name = "Name is required";
      isValid = false;
    }

    if (!formData.email.trim()) {
      newErrors.email = "Email is required";
      isValid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      newErrors.email = "Please enter a valid email address";
      isValid = false;
    }

    if (!formData.subject.trim()) {
      newErrors.subject = "Subject is required";
      isValid = false;
    }

    setErrors(newErrors);
    return isValid;
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    // Clear error when user starts typing
    if (errors[name as keyof typeof errors]) {
      setErrors(prev => ({
        ...prev,
        [name]: ""
      }));
    }
    // Clear success message when user starts typing again
    if (submitMessage.type === "success") {
      setSubmitMessage({ type: "", text: "" });
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    setIsSubmitting(true);
    setSubmitMessage({ type: "", text: "" });

    try {
      // Create FormData object for multipart/form-data
      const formDataToSend = new FormData();
      formDataToSend.append("name", formData.name);
      formDataToSend.append("email", formData.email);
      formDataToSend.append("subject", formData.subject);
      formDataToSend.append("message", formData.message);

      const response = await fetch("https://iaccs.org.in/contact_form_submit.php", {
        method: "POST",
        body: formDataToSend
      });

      if (response.ok) {
        try {
          const result = await response.json();
          
          // Adjust based on your API response structure
          if (result.success || result.message) {
            setSubmitMessage({ 
              type: "success", 
              text: "Message sent successfully!" 
            });
            // Reset form
            setFormData({
              name: "",
              email: "",
              subject: "",
              message: ""
            });
          } else {
            setSubmitMessage({ 
              type: "error", 
              text: result.error || "Failed to send message. Please try again." 
            });
          }
        } catch (jsonError) {
          // If response is not JSON, try text
          const textResult = await response.text();
          setSubmitMessage({ 
            type: "success", 
            text: "Message sent successfully!" 
          });
          // Reset form
          setFormData({
            name: "",
            email: "",
            subject: "",
            message: ""
          });
        }
      } else {
        setSubmitMessage({ 
          type: "error", 
          text: `Server error: ${response.status}. Please try again.` 
        });
      }
    } catch (error) {
      console.error("Error submitting form:", error);
      setSubmitMessage({ 
        type: "error", 
        text: "Network error. Please check your connection and try again." 
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <>
      <header className="relative w-full flex flex-col gap-48 bg-transparent bg-[url(/assets/images/banner-img.png)] bg-cover bg-center py-32 mt-20 bg-gray-500">
        <div className="absolute inset-0 bg-black opacity-50"></div>
        <div className="container mx-auto px-[110px] relative z-[1]">
          <div className="w-full max-w-[642px] flex flex-col gap-12">
            <h1 className="w-full max-w-[640px] font-bold text-white text-[64px] tracking-[0] leading-[76.8px]">
              Contact Us
            </h1>
            <p className="text-lg text-white">
              Have any questions or want to work with us? Feel free to reach out anytime.
            </p>
          </div>
        </div>
      </header>

      <section className="w-full flex flex-col gap-16 py-16 px-[110px]">
        <div className="w-full gap-[82px]">
          <div className="flex flex-wrap -mx-4">
            <div className="lg:w-1/2 w-full px-4">
              <div className="space-y-8">
                <div className="">
                  <h3 className="text-2xl font-semibold mb-4">Head Office Address</h3>
                  <div className="space-y-4 text-gray-700">
                    <p>
                      <strong>Address:</strong>
                      168, Mathkal, Nazrul Sarani, Dumdum Cantonment, Kolkata, 700065
                    </p>
                    <p>
                      <strong>Phone:</strong>
                      +91 8918505434
                    </p>
                    <p>
                      <strong>Official Email Address:</strong>
                      - <a href="mailto:admin@iaccs.org.in" className="text-blue-600 hover:underline">
                        admin@iaccs.org.in
                      </a>
                    </p>
                  </div>
                </div>

                {/* <div className="">
                  <h3 className="text-lg font-semibold mb-3">Find Us On Map</h3>
                  <div className="w-full h-64 xl:h-96 bg-gray-300 rounded-lg flex items-center justify-center">
                    <span className="text-gray-600">Google Map Placeholder</span>
                  </div>
                </div> */}
              </div>
            </div>

            <div className="lg:w-1/2 w-full px-4">
              <div className="">
                <h3 className="text-2xl font-semibold mb-6">Send Us a Message</h3>

                {/* Success/Error Message */}
                {submitMessage.text && (
                  <div 
                    className={`mb-6 p-4 rounded-lg transition-opacity duration-300 ${
                      submitMessage.type === "success" 
                        ? "bg-green-100 text-green-700 border border-green-300" 
                        : "bg-red-100 text-red-700 border border-red-300"
                    }`}
                  >
                    <div className="flex justify-between items-center">
                      <span>{submitMessage.text}</span>
                      {submitMessage.type === "error" && (
                        <button 
                          onClick={() => setSubmitMessage({ type: "", text: "" })}
                          className="text-gray-500 hover:text-gray-700 ml-4"
                        >
                          ×
                        </button>
                      )}
                    </div>
                  </div>
                )}

                <form onSubmit={handleSubmit}>
                  <div className="mb-4">
                    <label className="block text-gray-600 mb-1">Your Name *</label>
                    <input
                      type="text"
                      name="name"
                      value={formData.name}
                      onChange={handleChange}
                      className={`w-full p-3 rounded-lg border ${
                        errors.name 
                          ? "border-red-500 focus:border-red-500" 
                          : "border-gray-300 focus:border-yellow-500"
                      } focus:outline-none focus:ring-0`}
                      placeholder="Enter your name"
                    />
                    {errors.name && (
                      <p className="text-red-500 text-sm mt-1">{errors.name}</p>
                    )}
                  </div>

                  <div className="mb-4">
                    <label className="block text-gray-600 mb-1">Your Email *</label>
                    <input
                      type="email"
                      name="email"
                      value={formData.email}
                      onChange={handleChange}
                      className={`w-full p-3 rounded-lg border ${
                        errors.email 
                          ? "border-red-500 focus:border-red-500" 
                          : "border-gray-300 focus:border-yellow-500"
                      } focus:outline-none focus:ring-0`}
                      placeholder="Enter your email"
                    />
                    {errors.email && (
                      <p className="text-red-500 text-sm mt-1">{errors.email}</p>
                    )}
                  </div>

                  <div className="mb-4">
                    <label className="block text-gray-600 mb-1">Subject *</label>
                    <input
                      type="text"
                      name="subject"
                      value={formData.subject}
                      onChange={handleChange}
                      className={`w-full p-3 rounded-lg border ${
                        errors.subject 
                          ? "border-red-500 focus:border-red-500" 
                          : "border-gray-300 focus:border-yellow-500"
                      } focus:outline-none focus:ring-0`}
                      placeholder="Enter subject"
                    />
                    {errors.subject && (
                      <p className="text-red-500 text-sm mt-1">{errors.subject}</p>
                    )}
                  </div>

                  <div className="mb-6">
                    <label className="block text-gray-600 mb-1">Message</label>
                    <textarea
                      rows={5}
                      name="message"
                      value={formData.message}
                      onChange={handleChange}
                      className="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-0 focus:border-yellow-500"
                      placeholder="Write your message"
                    ></textarea>
                  </div>

                  <button
                    type="submit"
                    disabled={isSubmitting}
                    className={`h-auto px-8 py-4 bg-yellow-500 text-[#000000] font-medium text-base rounded backdrop-blur-2xl backdrop-brightness-[100%] ${
                      isSubmitting 
                        ? "opacity-50 cursor-not-allowed" 
                        : "hover:opacity-90"
                    }`}
                  >
                    {isSubmitting ? "Sending..." : "Send Message"}
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}