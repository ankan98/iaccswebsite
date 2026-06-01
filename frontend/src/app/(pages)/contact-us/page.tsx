"use client";
import { useState, useEffect } from "react";

export default function Contact() {
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    subject: "",
    message: "",
  });

  const [errors, setErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [toast, setToast] = useState<{ type: "success" | "error"; message: string } | null>(null);

  // Auto-hide toast after 5 seconds
  useEffect(() => {
    if (toast) {
      const timer = setTimeout(() => {
        setToast(null);
      }, 5000);
      return () => clearTimeout(timer);
    }
  }, [toast]);

  const validateForm = () => {
    const newErrors: Record<string, string> = {};

    if (!formData.name.trim()) {
      newErrors.name = "Name is required";
    }

    if (!formData.email.trim()) {
      newErrors.email = "Email is required";
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      newErrors.email = "Please enter a valid email address";
    }

    if (!formData.subject.trim()) {
      newErrors.subject = "Subject is required";
    }

    if (!formData.message.trim()) {
      newErrors.message = "Message is required";
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }));

    // Clear error when user starts typing
    if (errors[name]) {
      setErrors((prev) => {
        const newErrors = { ...prev };
        delete newErrors[name];
        return newErrors;
      });
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!validateForm()) {
      setToast({
        type: "error",
        message: "Please fill in all required fields",
      });
      return;
    }

    setIsSubmitting(true);
    setToast(null);

    try {
      const formDataToSend = new FormData();
      formDataToSend.append("name", formData.name);
      formDataToSend.append("email", formData.email);
      formDataToSend.append("subject", formData.subject);
      formDataToSend.append("message", formData.message);

      const response = await fetch("https://iaccs.org.in/contact_form_submit.php", {
        method: "POST",
        body: formDataToSend,
      });

      if (response.ok) {
        try {
          const result = await response.json();

          if (result.success === true) {
            setToast({
              type: "success",
              message: result.message || "Message sent successfully!",
            });

            // Reset form
            setFormData({
              name: "",
              email: "",
              subject: "",
              message: "",
            });
          } else {
            // Handle validation errors from API
            let errorMessage = "Failed to send message. Please try again.";
            
            if (result.errors) {
              // Get all error messages from the errors object
              const errorMessages = Object.values(result.errors).filter(Boolean);
              if (errorMessages.length > 0) {
                errorMessage = errorMessages.join(". ");
              }
            } else if (result.error) {
              errorMessage = result.error;
            } else if (result.message) {
              errorMessage = result.message;
            }
            
            setToast({
              type: "error",
              message: errorMessage,
            });
          }
        } catch {
          // If response is not JSON, assume success
          setToast({
            type: "success",
            message: "Message sent successfully!",
          });

          // Reset form
          setFormData({
            name: "",
            email: "",
            subject: "",
            message: "",
          });
        }
      } else {
        setToast({
          type: "error",
          message: `Server error: ${response.status}. Please try again.`,
        });
      }
    } catch (error) {
      console.error("Error submitting form:", error);
      setToast({
        type: "error",
        message: "Network error. Please check your connection and try again.",
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="bg-white">
      {/* Toast Notification */}
      {toast && (
        <div className="fixed top-24 right-4 z-50 animate-slide-in">
          <div
            className={`rounded-lg shadow-lg p-4 max-w-sm ${
              toast.type === "success"
                ? "bg-green-50 border border-green-200"
                : "bg-red-50 border border-red-200"
            }`}
          >
            <div className="flex items-start">
              <div
                className={`flex-shrink-0 w-6 h-6 ${
                  toast.type === "success" ? "text-green-500" : "text-red-500"
                }`}
              >
                {toast.type === "success" ? (
                  <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                  </svg>
                ) : (
                  <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                )}
              </div>
              <div className="ml-3">
                <p
                  className={`text-sm font-medium ${
                    toast.type === "success" ? "text-green-800" : "text-red-800"
                  }`}
                >
                  {toast.message}
                </p>
              </div>
              <button
                onClick={() => setToast(null)}
                className="ml-auto -mx-1.5 -my-1.5 rounded-lg p-1.5 inline-flex h-8 w-8 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200"
              >
                <span className="sr-only">Close</span>
                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    fillRule="evenodd"
                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                    clipRule="evenodd"
                  />
                </svg>
              </button>
            </div>
          </div>
        </div>
      )}


      <section className="w-full bg-white flex flex-col gap-16 py-16 xl:px-[110px] md:px-20 px-5 mt-[40px]">
        <div className="w-full gap-[82px]">
          <h1 className="w-full font-bold text-gray-900 xl:text-[48px] md:text-4xl sm:text-4xl text-3xl !leading-tight mb-[20px]" style={{ fontFamily: "Georgia, serif" }}>Contact Us</h1>

          <div className="flex flex-wrap -mx-4 gap-y-10">
            <div className="lg:w-1/2 w-full px-4">
              <div className="space-y-8">
                <div className="">
                  <h3 className="text-2xl font-semibold mb-4 text-gray-800" style={{color:'#1a4075'}}>Head Office Address</h3>

                  <div className="space-y-4 text-gray-700 text-base md:text-lg">
                    <p>
                      <strong>Address:</strong> Mathkal, Nazrul Sarani, Dumdum Cantonment,
                      Kolkata, 700065
                    </p>
                    <p>
                      <strong>Official Email Address:</strong>
                      <a
                        href="mailto:admin@iaccs.org.in"
                        className="text-blue-600 hover:underline ml-1"
                        style={{color:'#1a4075'}}
                      >
                        admin@iaccs.org.in
                      </a>
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div className="lg:w-1/2 w-full px-4">
              <div className="">
                <h3 className="text-2xl font-semibold mb-6 text-gray-800" style={{color:'#1a4075'}}>Send Us a Message</h3>

                <form onSubmit={handleSubmit}>
                  <div className="mb-4">
                    <label className="block text-gray-700 font-medium mb-1">
                      Your Name <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="name"
                      value={formData.name}
                      onChange={handleChange}
                      className={`w-full p-3 bg-white text-gray-900 border focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 ${
                        errors.name ? "border-red-500" : "border-gray-300"
                      }`}
                      placeholder="Enter your name"
                    />
                    {errors.name && <p className="text-red-500 text-sm mt-1">{errors.name}</p>}
                  </div>

                  <div className="mb-4">
                    <label className="block text-gray-700 font-medium mb-1">
                      Your Email <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="email"
                      name="email"
                      value={formData.email}
                      onChange={handleChange}
                      className={`w-full p-3 bg-white text-gray-900  border focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 ${
                        errors.email ? "border-red-500" : "border-gray-300"
                      }`}
                      placeholder="Enter your email"
                    />
                    {errors.email && <p className="text-red-500 text-sm mt-1">{errors.email}</p>}
                  </div>

                  <div className="mb-4">
                    <label className="block text-gray-700 font-medium mb-1">
                      Subject <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="subject"
                      value={formData.subject}
                      onChange={handleChange}
                      className={`w-full p-3 bg-white text-gray-900 border focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 ${
                        errors.subject ? "border-red-500" : "border-gray-300"
                      }`}
                      placeholder="Enter subject"
                    />
                    {errors.subject && <p className="text-red-500 text-sm mt-1">{errors.subject}</p>}
                  </div>

                  <div className="mb-4">
                    <label className="block text-gray-700 font-medium mb-1">
                      Message <span className="text-red-500">*</span>
                    </label>
                    <textarea
                      rows={5}
                      name="message"
                      value={formData.message}
                      onChange={handleChange}
                      className={`w-full p-3 bg-white text-gray-900 border focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 resize-none ${
                        errors.message ? "border-red-500" : "border-gray-300"
                      }`}
                      placeholder="Write your message"
                    ></textarea>
                    {errors.message && <p className="text-red-500 text-sm mt-1">{errors.message}</p>}
                  </div>

                  <button
                    type="submit"
                    disabled={isSubmitting}
                    className={`w-full sm:w-auto px-8 sm:px-16 py-4 font-semibold rounded-full border-2 border-solid border-black transition ${
                      isSubmitting
                        ? "opacity-60 cursor-not-allowed"
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

    </div>
  );
}


