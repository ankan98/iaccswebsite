"use client";
import React from "react";
import { useState, useEffect, useRef } from "react";

export default function Membership() {
  const [formData, setFormData] = useState({
    // Personal Information
    name: "",
    father_name: "",
    dob: "",
    age: "",
    gender: "",
    
    // Address
    address: "",
    city: "",
    district: "",
    pin: "",
    state: "",
    
    // Contact
    mobile: "",
    email: "",
    nationality: "",
    
    // Education
    education: "",
    education_status: "",
    academic_session: "",
    college_name: "",
    university_name: "",
    
    // Employment (for employees)
    employed: "",
    employment_type: "",
    hospital_name: "",
    designation: "",
    employee_id: "",
    
    // Membership
    membership_plan: "",
    amount: "",
  });
  
  const [files, setFiles] = useState({
    photo: null as File | null,
    id_proof: null as File | null,
    education_doc: null as File | null,
    student_id: null as File | null,
    employment_proof: null as File | null,
  });
  
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [toast, setToast] = useState<{type: 'success' | 'error', message: string} | null>(null);
  const [applicantType, setApplicantType] = useState<"student" | "employee" | "">("");
  const [educationalQualification, setEducationalQualification] = useState<string[]>([]);
  const [acceptedTerms, setAcceptedTerms] = useState(false);
  
  // Refs for scroll to field
  const formRef = useRef<HTMLFormElement>(null);
  const nameRef = useRef<HTMLInputElement>(null);
  const emailRef = useRef<HTMLInputElement>(null);
  const mobileRef = useRef<HTMLInputElement>(null);
  const termsRef = useRef<HTMLInputElement>(null);

  const fileInputRefs = {
    photo: useRef<HTMLInputElement>(null),
    id_proof: useRef<HTMLInputElement>(null),
    education_doc: useRef<HTMLInputElement>(null),
    student_id: useRef<HTMLInputElement>(null),
    employment_proof: useRef<HTMLInputElement>(null),
  };

  const membershipFee = applicantType === "student" ? "50" : applicantType === "employee" ? "100" : "";

  // Auto-hide toast after 5 seconds
  useEffect(() => {
    if (toast) {
      const timer = setTimeout(() => {
        setToast(null);
      }, 5000);
      
      return () => clearTimeout(timer);
    }
  }, [toast]);

  // Update amount when applicant type changes
  useEffect(() => {
    setFormData(prev => ({
      ...prev,
      membership_plan: applicantType,
      amount: membershipFee
    }));
  }, [applicantType, membershipFee]);

  // Function to scroll to element
  const scrollToElement = (element: HTMLElement | null) => {
    if (element) {
      const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
      const offsetPosition = elementPosition - 100; // 100px offset from top
      
      window.scrollTo({
        top: offsetPosition,
        behavior: 'smooth'
      });
      
      // Add focus for accessibility
      setTimeout(() => {
        if (element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement || element instanceof HTMLSelectElement) {
          element.focus();
        }
      }, 500);
    }
  };

  // Validation - Only name, email, and phone are mandatory
  const validateForm = () => {
    const newErrors: Record<string, string> = {};
    
    // ONLY THESE THREE FIELDS ARE MANDATORY
    if (!formData.name.trim()) {
      newErrors.name = "Name is required";
      if (!nameRef.current) {
        // Set timeout to ensure ref is available
        setTimeout(() => scrollToElement(nameRef.current), 100);
      } else {
        scrollToElement(nameRef.current);
      }
    }
    
    if (!formData.email.trim()) {
      newErrors.email = "Email is required";
      if (!newErrors.name && !emailRef.current) {
        setTimeout(() => scrollToElement(emailRef.current), 100);
      } else if (!newErrors.name) {
        scrollToElement(emailRef.current);
      }
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      newErrors.email = "Please enter a valid email address";
      if (!newErrors.name && !emailRef.current) {
        setTimeout(() => scrollToElement(emailRef.current), 100);
      } else if (!newErrors.name) {
        scrollToElement(emailRef.current);
      }
    }
    
    if (!formData.mobile.trim()) {
      newErrors.mobile = "Mobile number is required";
      if (!newErrors.name && !newErrors.email && !mobileRef.current) {
        setTimeout(() => scrollToElement(mobileRef.current), 100);
      } else if (!newErrors.name && !newErrors.email) {
        scrollToElement(mobileRef.current);
      }
    } else if (!/^[0-9]{10}$/.test(formData.mobile)) {
      newErrors.mobile = "Please enter a valid 10-digit mobile number";
      if (!newErrors.name && !newErrors.email && !mobileRef.current) {
        setTimeout(() => scrollToElement(mobileRef.current), 100);
      } else if (!newErrors.name && !newErrors.email) {
        scrollToElement(mobileRef.current);
      }
    }
    
    // Terms acceptance is mandatory
    if (!acceptedTerms) {
      newErrors.terms = "You must accept the terms and conditions";
      if (!newErrors.name && !newErrors.email && !newErrors.mobile && !termsRef.current) {
        setTimeout(() => scrollToElement(termsRef.current), 100);
      } else if (!newErrors.name && !newErrors.email && !newErrors.mobile) {
        scrollToElement(termsRef.current);
      }
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    
    // Clear error when user starts typing
    if (errors[name]) {
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors[name];
        return newErrors;
      });
    }
  };

  const handleFileChange = (fieldName: keyof typeof files, e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0] || null;
    setFiles(prev => ({
      ...prev,
      [fieldName]: file
    }));
    
    // Clear file error
    if (errors[fieldName]) {
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors[fieldName];
        return newErrors;
      });
    }
  };

  const handleQualificationChange = (qualification: string) => {
    const newQualifications = educationalQualification.includes(qualification)
      ? educationalQualification.filter(q => q !== qualification)
      : [...educationalQualification, qualification];
    
    setEducationalQualification(newQualifications);
    setFormData(prev => ({
      ...prev,
      education: newQualifications.join(", ")
    }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    setIsSubmitting(true);
    setToast(null);

    try {
      const formDataToSend = new FormData();
      
      // Add all form data (even empty strings)
      Object.entries(formData).forEach(([key, value]) => {
        formDataToSend.append(key, value ? value.toString() : "");
      });
      
      // Add educational qualifications as a string
      formDataToSend.append("education", educationalQualification.join(", "));
      
      // Add files if they exist
      Object.entries(files).forEach(([key, file]) => {
        if (file) formDataToSend.append(key, file);
      });

      // Add applicant type specific data
      if (applicantType === "student") {
        formDataToSend.append("membership_plan", "student");
      } else if (applicantType === "employee") {
        formDataToSend.append("membership_plan", "premium");
      }

      const response = await fetch("https://iaccs.org.in/membership_form_submit.php", {
        method: "POST",
        body: formDataToSend
      });

      if (response.ok) {
        try {
          const result = await response.json();
          
          if (result.success || result.message) {
            setToast({
              type: 'success',
              message: "Application submitted successfully!"
            });
            
            // Scroll to top to show success toast
            window.scrollTo({
              top: 0,
              behavior: 'smooth'
            });
            
            // Reset form
            setFormData({
              name: "",
              father_name: "",
              dob: "",
              age: "",
              gender: "",
              address: "",
              city: "",
              district: "",
              pin: "",
              state: "",
              mobile: "",
              email: "",
              nationality: "",
              education: "",
              education_status: "",
              academic_session: "",
              college_name: "",
              university_name: "",
              employed: "",
              employment_type: "",
              hospital_name: "",
              designation: "",
              employee_id: "",
              membership_plan: "",
              amount: "",
            });
            
            setFiles({
              photo: null,
              id_proof: null,
              education_doc: null,
              student_id: null,
              employment_proof: null,
            });
            
            setEducationalQualification([]);
            setAcceptedTerms(false);
            setApplicantType("");
          } else {
            setToast({
              type: 'error',
              message: result.error || "Failed to submit application. Please try again."
            });
          }
        } catch (jsonError) {
          // If response is not JSON, assume success
          setToast({
            type: 'success',
            message: "Application submitted successfully!"
          });
          
          // Scroll to top to show success toast
          window.scrollTo({
            top: 0,
            behavior: 'smooth'
          });
          
          // Reset form
          setFormData({
            name: "",
            father_name: "",
            dob: "",
            age: "",
            gender: "",
            address: "",
            city: "",
            district: "",
            pin: "",
            state: "",
            mobile: "",
            email: "",
            nationality: "",
            education: "",
            education_status: "",
            academic_session: "",
            college_name: "",
            university_name: "",
            employed: "",
            employment_type: "",
            hospital_name: "",
            designation: "",
            employee_id: "",
            membership_plan: "",
            amount: "",
          });
          
          setFiles({
            photo: null,
            id_proof: null,
            education_doc: null,
            student_id: null,
            employment_proof: null,
          });
          
          setEducationalQualification([]);
          setAcceptedTerms(false);
          setApplicantType("");
        }
      } else {
        setToast({
          type: 'error',
          message: `Server error: ${response.status}. Please try again.`
        });
      }
    } catch (error) {
      console.error("Error submitting form:", error);
      setToast({
        type: 'error',
        message: "Network error. Please check your connection and try again."
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <>
      {/* Toast Notification */}
      {toast && (
        <div className="fixed top-24 right-4 z-50 animate-slide-in">
          <div className={`rounded-lg shadow-lg p-4 max-w-sm ${
            toast.type === 'success' 
              ? 'bg-green-50 border border-green-200' 
              : 'bg-red-50 border border-red-200'
          }`}>
            <div className="flex items-start">
              <div className={`flex-shrink-0 w-6 h-6 ${
                toast.type === 'success' ? 'text-green-500' : 'text-red-500'
              }`}>
                {toast.type === 'success' ? (
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
                <p className={`text-sm font-medium ${
                  toast.type === 'success' ? 'text-green-800' : 'text-red-800'
                }`}>
                  {toast.message}
                </p>
              </div>
              <button
                onClick={() => setToast(null)}
                className="ml-auto -mx-1.5 -my-1.5 rounded-lg p-1.5 inline-flex h-8 w-8 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200"
              >
                <span className="sr-only">Close</span>
                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ================= ORIGINAL BANNER ================= */}
      <header className="relative mt-20 w-full bg-[url(/assets/images/banner-img.png)] bg-cover bg-center py-28">
        <div className="absolute inset-0 bg-black/60" />
        <div className="relative z-10 mx-auto max-w-6xl px-6 md:px-[110px]">
          <h1 className="text-5xl font-bold text-white">
            Membership Application
          </h1>
          <p className="mt-3 text-lg text-white">
            Please fill the form carefully in BLOCK letters.
          </p>
        </div>
      </header>

      {/* ================= ENHANCED FORM ================= */}
      <section className="bg-gray-50 px-6 py-20 md:px-[110px]">
        <div className="mx-auto max-w-6xl space-y-8">
          
          {/* Progress Indicator */}
          <div className="bg-white rounded-xl p-6 shadow-sm">
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center gap-2">
                <div className="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                  <span className="text-white font-semibold">1</span>
                </div>
                <span className="font-medium text-gray-700">Personal Info</span>
              </div>
              
              <div className="h-1 w-24 bg-gray-200"></div>
              
              <div className="flex items-center gap-2">
                <div className={`w-8 h-8 rounded-full flex items-center justify-center ${applicantType ? 'bg-blue-600' : 'bg-gray-200'}`}>
                  <span className={`font-semibold ${applicantType ? 'text-white' : 'text-gray-500'}`}>2</span>
                </div>
                <span className={`font-medium ${applicantType ? 'text-gray-700' : 'text-gray-400'}`}>Details</span>
              </div>
              
              <div className="h-1 w-24 bg-gray-200"></div>
              
              <div className="flex items-center gap-2">
                <div className={`w-8 h-8 rounded-full flex items-center justify-center ${applicantType ? 'bg-blue-600' : 'bg-gray-200'}`}>
                  <span className={`font-semibold ${applicantType ? 'text-white' : 'text-gray-500'}`}>3</span>
                </div>
                <span className={`font-medium ${applicantType ? 'text-gray-700' : 'text-gray-400'}`}>Documents</span>
              </div>
            </div>
          </div>

          <form ref={formRef} onSubmit={handleSubmit} className="space-y-5">
            {/* PERSONAL INFORMATION */}
            <div className="rounded-xl bg-white p-8 shadow-md border border-gray-100">
              <h2 className="mb-8 pb-3 border-b text-2xl font-bold text-gray-800">
                Personal Information
              </h2>

              <div className="grid gap-6 md:grid-cols-2">
                <Input 
                  label="Name (IN BLOCK LETTERS) *" 
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  placeholder="Enter your full name"
                  error={errors.name}
                  ref={nameRef}
                />
                <Input 
                  label="Father's Name" 
                  name="father_name"
                  value={formData.father_name}
                  onChange={handleChange}
                  placeholder="Enter father's name"
                />
                <Input 
                  label="Date of Birth" 
                  name="dob"
                  type="date"
                  value={formData.dob}
                  onChange={handleChange}
                />
                <Input 
                  label="Age" 
                  name="age"
                  type="number"
                  value={formData.age}
                  onChange={handleChange}
                  placeholder="Enter age"
                />

                <div className="md:col-span-2">
                  <Label>Gender</Label>
                  <div className="flex gap-8 mt-2">
                    {["Male", "Female", "Transgender"].map((option) => (
                      <Radio 
                        key={option}
                        name="gender" 
                        label={option} 
                        checked={formData.gender === option}
                        onChange={() => setFormData(prev => ({ ...prev, gender: option }))}
                      />
                    ))}
                  </div>
                </div>
              </div>
            </div>

            {/* ADDRESS */}
            <div className="rounded-xl bg-white p-8 shadow-md border border-gray-100">
              <h2 className="mb-8 pb-3 border-b text-2xl font-bold text-gray-800">
                Residential Address
              </h2>

              <div className="grid gap-6 md:grid-cols-2">
                <div className="md:col-span-2">
                  <Textarea 
                    label="Address" 
                    name="address"
                    value={formData.address}
                    onChange={handleChange}
                    rows={3} 
                    placeholder="Enter your complete address"
                  />
                </div>
                <Input 
                  label="City" 
                  name="city"
                  value={formData.city}
                  onChange={handleChange}
                  placeholder="Enter city" 
                />
                <Input 
                  label="District" 
                  name="district"
                  value={formData.district}
                  onChange={handleChange}
                  placeholder="Enter district" 
                />
                <Input 
                  label="PIN Code" 
                  name="pin"
                  value={formData.pin}
                  onChange={handleChange}
                  placeholder="Enter PIN code" 
                />
                <Input 
                  label="State" 
                  name="state"
                  value={formData.state}
                  onChange={handleChange}
                  placeholder="Enter state" 
                />
              </div>
            </div>

            {/* CONTACT */}
            <div className="rounded-xl bg-white p-8 shadow-md border border-gray-100">
              <h2 className="mb-8 pb-3 border-b text-2xl font-bold text-gray-800">
                Contact Details
              </h2>

              <div className="grid gap-6 md:grid-cols-2">
                <Input 
                  label="Mobile Number *" 
                  name="mobile"
                  value={formData.mobile}
                  onChange={handleChange}
                  placeholder="Enter 10-digit mobile number" 
                  type="tel"
                  error={errors.mobile}
                  ref={mobileRef}
                />
                <div>
                  <Label>Nationality</Label>
                  <div className="flex gap-8 mt-2">
                    {["Indian", "Others"].map((option) => (
                      <Radio 
                        key={option}
                        name="nationality" 
                        label={option} 
                        checked={formData.nationality === option}
                        onChange={() => setFormData(prev => ({ ...prev, nationality: option }))}
                      />
                    ))}
                  </div>
                </div>
                <Input 
                  label="Email Address *" 
                  name="email"
                  value={formData.email}
                  onChange={handleChange}
                  type="email" 
                  placeholder="Enter email address"
                  error={errors.email}
                  ref={emailRef}
                />
              </div>
            </div>

            {/* EDUCATION */}
            <div className="rounded-xl bg-white p-8 shadow-md border border-gray-100">
              <h2 className="mb-8 pb-3 border-b text-2xl font-bold text-gray-800">
                Educational Details
              </h2>

              <div className="space-y-6">
                <div>
                  <Label>Educational Qualification</Label>
                  <div className="flex gap-8 mt-2">
                    {["Diploma", "Bachelor", "Masters"].map((qual) => (
                      <Checkbox 
                        key={qual}
                        label={qual} 
                        checked={educationalQualification.includes(qual)}
                        onChange={() => handleQualificationChange(qual)}
                      />
                    ))}
                  </div>
                </div>

                <div>
                  <Label>Status</Label>
                  <div className="flex gap-8 mt-2">
                    {["Pursuing", "Completed"].map((option) => (
                      <Radio 
                        key={option}
                        name="education_status" 
                        label={option} 
                        checked={formData.education_status === option}
                        onChange={() => setFormData(prev => ({ ...prev, education_status: option }))}
                      />
                    ))}
                  </div>
                </div>

                <Input 
                  label="Academic Session (only if pursuing)" 
                  name="academic_session"
                  value={formData.academic_session}
                  onChange={handleChange}
                  placeholder="e.g., 2023-2027" 
                />
                <Input 
                  label="College / Institution Name" 
                  name="college_name"
                  value={formData.college_name}
                  onChange={handleChange}
                  placeholder="Enter institution name" 
                />
                <Input 
                  label="University Name" 
                  name="university_name"
                  value={formData.university_name}
                  onChange={handleChange}
                  placeholder="Enter university name" 
                />
              </div>
            </div>

            {/* APPLICANT TYPE */}
            <div className="rounded-xl bg-white p-8 shadow-md border border-gray-100">
              <h2 className="mb-8 pb-3 border-b text-2xl font-bold text-gray-800">
                Applicant Details
              </h2>

              <div className="grid gap-6 md:grid-cols-2">
                <div>
                  <Label>Applicant Type</Label>
                  <div className="relative">
                    <div className="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400">
                      <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
                      </svg>
                    </div>
                    <select
                      name="applicant_type"
                      className="w-full pl-12 pr-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
                      value={applicantType}
                      onChange={(e) => setApplicantType(e.target.value as any)}
                    >
                      <option value="">Select applicant type</option>
                      <option value="student">Student</option>
                      <option value="employee">Employee</option>
                    </select>
                    <div className="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none">
                      <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                      </svg>
                    </div>
                  </div>
                </div>

                <div>
                  <Label>Membership Fee</Label>
                  <div className="relative">
                    <div className="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-700 font-medium">
                      ₹
                    </div>
                    <input
                      value={membershipFee ? `₹${membershipFee}` : ""}
                      disabled
                      className="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-300 rounded-lg font-semibold text-gray-700"
                    />
                  </div>
                </div>
              </div>
            </div>

            {/* EMPLOYMENT */}
            {applicantType === "employee" && (
              <div className="rounded-xl bg-white p-8 shadow-md border border-gray-100">
                <h2 className="mb-8 pb-3 border-b text-2xl font-bold text-gray-800">
                  Employment Details
                </h2>

                <div className="grid gap-6 md:grid-cols-2">
                  <SelectInput 
                    label="Currently Employed" 
                    name="employed"
                    value={formData.employed}
                    onChange={handleChange}
                    options={["Yes", "No"]} 
                  />
                  <SelectInput 
                    label="Type of Employment" 
                    name="employment_type"
                    value={formData.employment_type}
                    onChange={handleChange}
                    options={["Government", "Private"]} 
                  />
                  <Input 
                    label="Hospital / Institute Name" 
                    name="hospital_name"
                    value={formData.hospital_name}
                    onChange={handleChange}
                    placeholder="Enter organization name" 
                  />
                  <Input 
                    label="Present Designation" 
                    name="designation"
                    value={formData.designation}
                    onChange={handleChange}
                    placeholder="Enter designation" 
                  />
                  <Input 
                    label="Employee ID (if any)" 
                    name="employee_id"
                    value={formData.employee_id}
                    onChange={handleChange}
                    placeholder="Enter employee ID" 
                  />
                </div>
              </div>
            )}

            {/* DOCUMENT UPLOAD */}
            <div className="rounded-xl bg-white p-8 shadow-md border border-gray-100">
              <h2 className="mb-8 pb-3 border-b text-2xl font-bold text-gray-800">
                Document Upload (Optional)
              </h2>

              <div className="space-y-8">
                {/* Photo Upload */}
                <div className="space-y-2">
                  <Label>Recent Passport Size Photo (Optional)</Label>
                  <FileUpload
                    ref={fileInputRefs.photo}
                    onChange={(e) => handleFileChange("photo", e)}
                    acceptedTypes="image/*"
                    fileName={files.photo?.name}
                  />
                </div>

                {/* ID Proof Upload */}
                <div className="space-y-2">
                  <Label>ID Proof (Aadhaar/Passport/Voter ID) (Optional)</Label>
                  <FileUpload
                    ref={fileInputRefs.id_proof}
                    onChange={(e) => handleFileChange("id_proof", e)}
                    acceptedTypes=".pdf,.jpg,.jpeg,.png"
                    fileName={files.id_proof?.name}
                  />
                </div>

                {/* Education Document */}
                <div className="space-y-2">
                  <Label>Education Certificate/Degree (Optional)</Label>
                  <FileUpload
                    ref={fileInputRefs.education_doc}
                    onChange={(e) => handleFileChange("education_doc", e)}
                    acceptedTypes=".pdf,.jpg,.jpeg,.png"
                    fileName={files.education_doc?.name}
                  />
                </div>

                {/* Student ID (for students) */}
                {applicantType === "student" && (
                  <div className="space-y-2">
                    <Label>Student ID Card (Self Attested) (Optional)</Label>
                    <FileUpload
                      ref={fileInputRefs.student_id}
                      onChange={(e) => handleFileChange("student_id", e)}
                      acceptedTypes=".pdf,.jpg,.jpeg,.png"
                      fileName={files.student_id?.name}
                    />
                  </div>
                )}

                {/* Employment Proof (for employees) */}
                {applicantType === "employee" && (
                  <div className="space-y-2">
                    <Label>Employment Proof (Self Attested) (Optional)</Label>
                    <FileUpload
                      ref={fileInputRefs.employment_proof}
                      onChange={(e) => handleFileChange("employment_proof", e)}
                      acceptedTypes=".pdf,.jpg,.jpeg,.png"
                      fileName={files.employment_proof?.name}
                    />
                  </div>
                )}
              </div>
            </div>

            {/* TERMS AND SUBMIT */}
            <div className="bg-white rounded-xl p-8 shadow-md border border-gray-100">
              <div className="space-y-8">
                <div className="p-6 bg-blue-50 rounded-lg border border-blue-100" ref={termsRef as any}>
                  <label className="flex items-start gap-3 cursor-pointer">
                    <input 
                      type="checkbox" 
                      checked={acceptedTerms}
                      onChange={(e) => {
                        setAcceptedTerms(e.target.checked);
                        if (errors.terms) {
                          setErrors(prev => ({ ...prev, terms: "" }));
                        }
                      }}
                      className="mt-1 w-5 h-5 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500" 
                    />
                    <span className="text-gray-700">
                      I hereby declare that the information provided above is true and correct to the best of my knowledge. I agree to abide by the rules and regulations of the association. *
                    </span>
                  </label>
                  {errors.terms && <p className="text-red-500 text-sm mt-2">{errors.terms}</p>}
                </div>

                <div className="text-center">
                  <button 
                    type="submit"
                    disabled={isSubmitting}
                    className={`px-16 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-300 shadow-lg transform hover:-translate-y-0.5 ${
                      isSubmitting 
                        ? "opacity-50 cursor-not-allowed" 
                        : "hover:from-blue-700 hover:to-blue-800 hover:shadow-xl"
                    }`}
                  >
                    {isSubmitting ? "Submitting..." : "Submit Application"}
                  </button>
                  <p className="mt-4 text-sm text-gray-500">
                    * Required fields: Name, Email, Mobile Number, and Terms Acceptance
                  </p>
                </div>
              </div>
            </div>
          </form>
        </div>
      </section>

      <style jsx>{`
        @keyframes slide-in {
          from {
            transform: translateX(100%);
            opacity: 0;
          }
          to {
            transform: translateX(0);
            opacity: 1;
          }
        }
        
        .animate-slide-in {
          animation: slide-in 0.3s ease-out;
        }
      `}</style>
    </>
  );
}

/* ================= ENHANCED REUSABLE UI ================= */

function Label({ children }: { children: string }) {
  return (
    <label className="block text-sm font-semibold text-gray-700 mb-2">
      {children}
    </label>
  );
}

interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label: string;
  error?: string;
  ref?: React.Ref<HTMLInputElement>;
}

const Input = React.forwardRef<HTMLInputElement, InputProps>(
  ({ label, error, ...props }, ref) => {
    return (
      <div className="w-full">
        <Label>{label}</Label>
        <input
          ref={ref}
          {...props}
          className={`w-full px-4 py-3 text-white bg-white border rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors placeholder-gray-400 ${
            error ? "border-red-500" : "border-gray-300"
          }`}
        />
        {error && <p className="text-red-500 text-sm mt-1">{error}</p>}
      </div>
    );
  }
);

Input.displayName = "Input";

interface TextareaProps extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
  label: string;
  error?: string;
}

function Textarea({ label, error, ...props }: TextareaProps) {
  return (
    <div className="w-full">
      <Label>{label}</Label>
      <textarea
        {...props}
        className={`w-full px-4 py-3 text-white bg-white border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors resize-none placeholder-gray-400 ${
          error ? "border-red-500" : "border-gray-300"
        }`}
      />
      {error && <p className="text-red-500 text-sm mt-1">{error}</p>}
    </div>
  );
}

interface SelectInputProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  label: string;
  options: string[];
}

function SelectInput({ label, options, ...props }: SelectInputProps) {
  return (
    <div className="w-full">
      <Label>{label}</Label>
      <div className="relative">
        <select 
          {...props}
          className="w-full pl-4 pr-10 py-3 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none appearance-none transition-colors"
        >
          <option value="">Select {label.toLowerCase().replace('*', '').trim()}</option>
          {options.map((option: string) => (
            <option key={option} value={option}>
              {option}
            </option>
          ))}
        </select>
        <div className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none">
          <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
          </svg>
        </div>
      </div>
    </div>
  );
}

function Radio({ name, label, checked, onChange }: any) {
  return (
    <label className="flex items-center gap-3 cursor-pointer">
      <div className="relative">
        <input
          type="radio"
          name={name}
          checked={checked}
          onChange={onChange}
          className="sr-only peer"
        />
        <div className="w-5 h-5 border-2 border-gray-300 rounded-full flex items-center justify-center peer-checked:border-blue-600 transition-colors">
          {checked && <div className="w-2.5 h-2.5 bg-blue-600 rounded-full"></div>}
        </div>
      </div>
      <span className="text-gray-700">{label}</span>
    </label>
  );
}

function Checkbox({ label, checked, onChange }: any) {
  return (
    <label className="flex items-center gap-3 cursor-pointer">
      <div className="relative">
        <input
          type="checkbox"
          checked={checked}
          onChange={onChange}
          className="sr-only peer"
        />
        <div className="w-5 h-5 border-2 border-gray-300 rounded flex items-center justify-center peer-checked:bg-blue-600 peer-checked:border-blue-600 transition-colors">
          {checked && (
            <svg className="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
            </svg>
          )}
        </div>
      </div>
      <span className="text-gray-700">{label}</span>
    </label>
  );
}

interface FileUploadProps {
  onChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
  acceptedTypes: string;
  fileName?: string;
  error?: string;
}

const FileUpload = React.forwardRef<HTMLInputElement, FileUploadProps>(
  ({ onChange, acceptedTypes, fileName, error }, ref) => {
    return (
      <>
        <div className={`border-2 border-dashed rounded-lg p-8 text-center transition-colors duration-300 ${
          error ? "border-red-500 bg-red-50" : "border-gray-300 hover:border-blue-500"
        }`}>
          <div className="mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" className="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
          </div>
          <label htmlFor="file-upload" className="cursor-pointer">
            <p className="text-gray-600 mb-2">
              <span className="text-blue-600 font-medium hover:underline">
                Click to upload
              </span> or drag and drop
            </p>
            <p className="text-sm text-gray-500 mb-3">
              {acceptedTypes === "image/*" 
                ? "JPG, PNG up to 5MB" 
                : "PDF, JPG, PNG up to 5MB"}
            </p>
            {fileName && (
              <p className="text-sm text-green-600 font-medium">
                Selected: {fileName}
              </p>
            )}
          </label>
          <input
            ref={ref}
            type="file"
            className="hidden"
            id="file-upload"
            onChange={onChange}
            accept={acceptedTypes}
          />
        </div>
      </>
    );
  }
);

FileUpload.displayName = "FileUpload";