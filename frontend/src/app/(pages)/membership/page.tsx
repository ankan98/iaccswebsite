"use client";
import React from "react";
import { useState, useEffect, useRef } from "react";

function getPhpBaseUrl() {
  if (typeof window === "undefined") return "";
  const host = window.location.hostname;
  if (host === "localhost" || host === "127.0.0.1") {
    return "http://localhost/iaccs";
  }
  if (host.endsWith("agcinfosystem.com")) {
    return "https://iaccs.org.in";
  }
  return "";
}

function buildPhpUrl(path: string) {
  const base = getPhpBaseUrl();
  const normalizedPath = path.startsWith("/") ? path : `/${path}`;
  return base ? `${base}${normalizedPath}` : normalizedPath;
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null;
}

const createInitialFormData = () => ({
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
  payment_transaction_id: "",
});

const createInitialFiles = () => ({
  photo: null as File | null,
  id_proof: null as File | null,
  student_id: null as File | null,
  employment_proof: null as File | null,
  payment_proof: null as File | null,
});

export default function Membership() {
  const PAYMENT_CONFIG = {
    enableTimeCheck: true,
    windowSeconds: 300,
  };
  const [formData, setFormData] = useState(createInitialFormData);

  const [files, setFiles] = useState(createInitialFiles);

  const [errors, setErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [toast, setToast] = useState<{
    type: "success" | "error";
    message: string;
  } | null>(null);
  const [applicantType, setApplicantType] = useState<
    "student" | "professional" | ""
  >("");
  const [educationalQualification, setEducationalQualification] = useState<
    string[]
  >([]);
  const [acceptedTerms, setAcceptedTerms] = useState(false);
  const [currentStep, setCurrentStep] = useState<1 | 2>(1);
  const [paymentTimer, setPaymentTimer] = useState(PAYMENT_CONFIG.windowSeconds);
  const [referenceNumber, setReferenceNumber] = useState("");
  const [recordId, setRecordId] = useState("");
  const [showThankYou, setShowThankYou] = useState(false);
  const [thankYouData, setThankYouData] = useState<{
    reference_number?: string;
    membership_id?: string;
    payment_status?: string;
    status?: string;
    download_url?: string;
    message?: string;
  } | null>(null);

  // Refs for scroll to field
  const formRef = useRef<HTMLFormElement>(null);
  const nameRef = useRef<HTMLInputElement>(null);
  const emailRef = useRef<HTMLInputElement>(null);
  const mobileRef = useRef<HTMLInputElement>(null);
  const paymentTransactionRef = useRef<HTMLInputElement>(null);
  const termsRef = useRef<HTMLDivElement>(null);
  const paymentProofSectionRef = useRef<HTMLDivElement>(null);
  const paymentBarcodeRef = useRef<HTMLDivElement>(null);

  const fileInputRefs = {
    photo: useRef<HTMLInputElement>(null),
    id_proof: useRef<HTMLInputElement>(null),
    student_id: useRef<HTMLInputElement>(null),
    employment_proof: useRef<HTMLInputElement>(null),
    payment_proof: useRef<HTMLInputElement>(null),
  };

  const membershipFee =
    applicantType === "student"
      ? "50"
      : applicantType === "professional"
      ? "100"
      : "";

  // Auto-hide toast after 5 seconds
  useEffect(() => {
    if (toast) {
      const timer = setTimeout(() => {
        setToast(null);
      }, 7000);

      return () => clearTimeout(timer);
    }
  }, [toast]);

  // Update amount when applicant type changes
  useEffect(() => {
    setFormData((prev) => ({
      ...prev,
      membership_plan: applicantType,
      amount: membershipFee,
    }));
  }, [applicantType, membershipFee]);

  useEffect(() => {
    if (currentStep !== 2 || paymentTimer <= 0) return;
    const timer = setInterval(() => {
      setPaymentTimer((prev) => Math.max(0, prev - 1));
    }, 1000);

    return () => clearInterval(timer);
  }, [currentStep, paymentTimer]);

  useEffect(() => {
    const storedRef = window.localStorage.getItem("membership_reference_number");
    const storedRecordId = window.localStorage.getItem("membership_record_id");
    const expiresAt = window.localStorage.getItem(
      "membership_payment_expires_at"
    );
    const urlRef = new URLSearchParams(window.location.search).get("ref");

    if (urlRef) {
      setReferenceNumber(urlRef);
      setShowThankYou(true);
      loadStatus(urlRef);
      return;
    }

    if (storedRecordId && expiresAt) {
      const remaining = Math.max(
        0,
        Math.floor((Number(expiresAt) - Date.now()) / 1000)
      );
      if (remaining > 0) {
        if (storedRef) {
          setReferenceNumber(storedRef);
        }
        setRecordId(storedRecordId);
        setCurrentStep(2);
        setPaymentTimer(remaining);
      } else {
        window.localStorage.removeItem("membership_reference_number");
        window.localStorage.removeItem("membership_record_id");
        window.localStorage.removeItem("membership_payment_expires_at");
      }
    }
  }, []);

  useEffect(() => {
    if (currentStep === 2 && paymentTimer === 0) {
      window.localStorage.removeItem("membership_reference_number");
      window.localStorage.removeItem("membership_record_id");
      window.localStorage.removeItem("membership_payment_expires_at");
    }
  }, [currentStep, paymentTimer]);

  const formatTime = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, "0")}`;
  };

  const getPaymentExpiresAt = () => {
    const value = window.localStorage.getItem("membership_payment_expires_at");
    return value ? Number(value) : 0;
  };

  const isPaymentExpired =
    PAYMENT_CONFIG.enableTimeCheck &&
    (paymentTimer <= 0 ||
      (typeof window !== "undefined" &&
        getPaymentExpiresAt() > 0 &&
        Date.now() > getPaymentExpiresAt()));

  const loadStatus = async (ref: string) => {
    try {
      const url = `${buildPhpUrl(
        "membership_status_check.php"
      )}?ref=${encodeURIComponent(ref)}`;
      const res = await fetch(url, { method: "GET", cache: "no-store" });
      const raw = await res.text();

      let json: unknown;
      try {
        json = JSON.parse(raw);
      } catch (err) {
        console.error("Invalid JSON from membership_status_check.php", {
          url,
          status: res.status,
          raw: raw.slice(0, 250),
          err,
        });
        throw err;
      }

      if (!isRecord(json)) throw new Error("Invalid response shape");

      const data = isRecord(json.data) ? json.data : undefined;
      const downloadUrl =
        typeof json.download_url === "string"
          ? json.download_url
          : typeof data?.download_url === "string"
          ? data.download_url
          : "";

      if (json.success === true) {
        setThankYouData({
          reference_number:
            typeof data?.reference_number === "string"
              ? data.reference_number
              : undefined,
          membership_id:
            typeof data?.membership_id === "string" ? data.membership_id : undefined,
          payment_status:
            typeof data?.payment_status === "string"
              ? data.payment_status
              : undefined,
          status: typeof data?.status === "string" ? data.status : undefined,
          download_url: downloadUrl || undefined,
          message: undefined,
        });
      } else {
        setThankYouData({
          reference_number: ref,
          payment_status: undefined,
          status: undefined,
          membership_id: undefined,
          download_url: undefined,
          message:
            typeof json.message === "string" && json.message.trim()
              ? json.message
              : "Unable to fetch status. Please try again.",
        });
      }
    } catch (err) {
      console.error("Failed to load membership status", err);
      setThankYouData({
        reference_number: ref,
        payment_status: undefined,
        status: undefined,
        membership_id: undefined,
        download_url: undefined,
        message: "Unable to fetch status right now. Please try again.",
      });
    }
  };

  const setUrlRefParam = (ref: string) => {
    try {
      const url = new URL(window.location.href);
      url.searchParams.set("ref", ref);
      window.history.replaceState({}, "", `${url.pathname}${url.search}`);
    } catch {
      // ignore
    }
  };

  const clearUrlRefParam = () => {
    try {
      const url = new URL(window.location.href);
      url.searchParams.delete("ref");
      window.history.replaceState({}, "", `${url.pathname}${url.search}`);
    } catch {
      // ignore
    }
  };

  const resetFlow = () => {
    setShowThankYou(false);
    setThankYouData(null);
    setReferenceNumber("");
    setFormData(createInitialFormData());
    setFiles(createInitialFiles());
    setApplicantType("");
    setEducationalQualification([]);
    setAcceptedTerms(false);
    setCurrentStep(1);
    setRecordId("");
    setPaymentTimer(PAYMENT_CONFIG.windowSeconds);
    setErrors({});
    setToast(null);
    setIsSubmitting(false);
    try {
      window.localStorage.removeItem("membership_reference_number");
      window.localStorage.removeItem("membership_record_id");
      window.localStorage.removeItem("membership_payment_expires_at");
    } catch {
      // ignore
    }
    clearUrlRefParam();
  };

  // Function to scroll to element
  const scrollToElement = (element: HTMLElement | null) => {
    if (element) {
      const elementPosition =
        element.getBoundingClientRect().top + window.pageYOffset;
      const offsetPosition = elementPosition - 100; // 100px offset from top

      window.scrollTo({
        top: offsetPosition,
        behavior: "smooth",
      });

      // Add focus for accessibility
      setTimeout(() => {
        element.focus();
      }, 500);
    }
  };

  // Validation for Step 1
  const validateStep1 = () => {
    const newErrors: Record<string, string> = {};
    let firstErrorField: HTMLElement | null = null;

    // Name is required
    if (!formData.name.trim()) {
      newErrors.name = "Name is required";
      if (!firstErrorField && nameRef.current) {
        firstErrorField = nameRef.current;
      }
    }

    // Email is required
    if (!formData.email.trim()) {
      newErrors.email = "Email is required";
      if (!firstErrorField && emailRef.current) {
        firstErrorField = emailRef.current;
      }
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      newErrors.email = "Please enter a valid email address";
      if (!firstErrorField && emailRef.current) {
        firstErrorField = emailRef.current;
      }
    }

    // Mobile is required
    if (!formData.mobile.trim()) {
      newErrors.mobile = "Mobile number is required";
      if (!firstErrorField && mobileRef.current) {
        firstErrorField = mobileRef.current;
      }
    } else if (!/^[0-9]{10}$/.test(formData.mobile)) {
      newErrors.mobile = "Please enter a valid 10-digit mobile number";
      if (!firstErrorField && mobileRef.current) {
        firstErrorField = mobileRef.current;
      }
    }

    // Photo is required
    if (!files.photo) {
      newErrors.photo = "Passport size photo is required";
      if (!firstErrorField && fileInputRefs.photo.current) {
        firstErrorField = fileInputRefs.photo.current;
      }
    }

    // ID proof is required
    if (!files.id_proof) {
      newErrors.id_proof = "ID Card / Registration Certificate is required";
      if (!firstErrorField && fileInputRefs.id_proof.current) {
        firstErrorField = fileInputRefs.id_proof.current;
      }
    }

    // Terms acceptance is mandatory
    if (!acceptedTerms) {
      newErrors.terms = "You must accept the terms and conditions";
      if (!firstErrorField && termsRef.current) {
        firstErrorField = termsRef.current;
      }
    }

    setErrors(newErrors);

    // Scroll to first error field
    if (firstErrorField) {
      scrollToElement(firstErrorField);
    }

    return Object.keys(newErrors).length === 0;
  };

  // Validation for Step 2 (Payment)
  const validateStep2 = () => {
    const newErrors: Record<string, string> = {};
    let firstErrorField: HTMLElement | null = null;

    if (
      PAYMENT_CONFIG.enableTimeCheck &&
      (paymentTimer <= 0 ||
        (getPaymentExpiresAt() && Date.now() > getPaymentExpiresAt()))
    ) {
      setToast({
        type: "error",
        message:
          "Payment window expired. Please submit the application again.",
      });
      return false;
    }

    if (!recordId.trim()) {
      setToast({
        type: "error",
        message:
          "Missing record id. Please submit the application again.",
      });
      return false;
    }

    const hasTransactionId = !!formData.payment_transaction_id.trim();
    const hasPaymentProof = !!files.payment_proof;
    if (!hasTransactionId && !hasPaymentProof) {
      newErrors.payment_transaction_id =
        "Provide Transaction ID or upload payment proof";
      newErrors.payment_proof =
        "Provide Transaction ID or upload payment proof";
      if (!firstErrorField && paymentTransactionRef.current) {
        firstErrorField = paymentTransactionRef.current;
      }
    }

    setErrors(newErrors);

    if (firstErrorField) {
      scrollToElement(firstErrorField);
    }

    return Object.keys(newErrors).length === 0;
  };

  const handleChange = (
    e: React.ChangeEvent<
      HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement
    >
  ) => {
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

    if (name === "payment_transaction_id" && value.trim()) {
      setErrors((prev) => {
        const newErrors = { ...prev };
        delete newErrors.payment_proof;
        return newErrors;
      });
    }
  };

  const handleFileChange = (
    fieldName: keyof typeof files,
    e: React.ChangeEvent<HTMLInputElement>
  ) => {
    const file = e.target.files?.[0] || null;
    setFiles((prev) => ({
      ...prev,
      [fieldName]: file,
    }));

    // Clear file error
    if (errors[fieldName]) {
      setErrors((prev) => {
        const newErrors = { ...prev };
        delete newErrors[fieldName];
        return newErrors;
      });
    }

    if (fieldName === "payment_proof" && file) {
      setErrors((prev) => {
        const newErrors = { ...prev };
        delete newErrors.payment_transaction_id;
        return newErrors;
      });
      scrollToElement(paymentProofSectionRef.current);
    }
  };

  const handleQualificationChange = (qualification: string) => {
    const newQualifications = educationalQualification.includes(qualification)
      ? educationalQualification.filter((q) => q !== qualification)
      : [...educationalQualification, qualification];

    setEducationalQualification(newQualifications);
    setFormData((prev) => ({
      ...prev,
      education: newQualifications.join(", "),
    }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (currentStep === 1) {
      if (!validateStep1()) {
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
        } else if (applicantType === "professional") {
          formDataToSend.append("membership_plan", "premium");
        }

        const response = await fetch(
          buildPhpUrl("membership_form_submit.php"),
          {
            method: "POST",
            body: formDataToSend,
          }
        );

        if (response.ok) {
          try {
            const result = await response.json();

            if (result.success === true) {
              setToast({
                type: "success",
                message:
                  result.message ||
                  "Application submitted. Check your email for the QR code.",
              });

              if (result.record_id || result.reference_number) {
                if (result.reference_number) {
                  setReferenceNumber(result.reference_number);
                  window.localStorage.setItem(
                    "membership_reference_number",
                    result.reference_number
                  );
                }
                if (result.record_id) {
                  setRecordId(String(result.record_id));
                  window.localStorage.setItem(
                    "membership_record_id",
                    String(result.record_id)
                  );
                }
                window.localStorage.setItem(
                  "membership_payment_expires_at",
                  String(Date.now() + PAYMENT_CONFIG.windowSeconds * 1000)
                );
              } else {
                setToast({
                  type: "error",
                  message:
                    "Missing record id from server. Please submit again.",
                });
                return;
              }
              setCurrentStep(2);
              setPaymentTimer(PAYMENT_CONFIG.windowSeconds);
              setErrors({});

              setTimeout(() => {
                scrollToElement(paymentBarcodeRef.current);
              }, 200);
            } else {
              let errorMessage =
                result.error ||
                result.message ||
                "Failed to submit application. Please try again.";
              if (result.errors) {
                const errorMessages = Object.values(result.errors).filter(Boolean);
                if (errorMessages.length > 0) {
                  errorMessage = errorMessages.join(". ");
                }
              }
              setToast({
                type: "error",
                message: errorMessage,
              });
            }
          } catch {
            setToast({
              type: "error",
              message:
                "Unexpected server response. Please try submitting again.",
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

      return;
    }

    if (!validateStep2()) {
      return;
    }

    setIsSubmitting(true);
    setToast(null);

    try {
      const formDataToSend = new FormData();
      formDataToSend.append("action", "payment");
      formDataToSend.append("record_id", recordId);
      formDataToSend.append(
        "payment_transaction_id",
        formData.payment_transaction_id || ""
      );

      if (files.payment_proof) {
        formDataToSend.append("payment_proof", files.payment_proof);
      }

      const response = await fetch(
        buildPhpUrl("membership_form_submit.php"),
        {
          method: "POST",
          body: formDataToSend,
        }
      );

      if (response.ok) {
        const result = await response.json();

        if (result.success === true) {
          window.localStorage.removeItem("membership_reference_number");
          window.localStorage.removeItem("membership_record_id");
          window.localStorage.removeItem("membership_payment_expires_at");

          const refFromResponse =
            result.reference_number ||
            referenceNumber ||
            (result.redirect_url
              ? (() => {
                  try {
                    return (
                      new URL(
                        result.redirect_url,
                        window.location.origin
                      ).searchParams.get("ref") || ""
                    );
                  } catch {
                    return "";
                  }
                })()
              : "");

          if (refFromResponse) {
            setReferenceNumber(refFromResponse);
            setUrlRefParam(refFromResponse);
            await loadStatus(refFromResponse);
          }

          setShowThankYou(true);
          setToast(null);
        } else {
          let errorMessage =
            result.error ||
            result.message ||
            "Failed to submit payment. Please try again.";
          if (result.errors) {
            const errorMessages = Object.values(result.errors).filter(Boolean);
            if (errorMessages.length > 0) {
              errorMessage = errorMessages.join(". ");
            }
          }
          setToast({
            type: "error",
            message: errorMessage,
          });
        }
      } else {
        setToast({
          type: "error",
          message: `Server error: ${response.status}. Please try again.`,
        });
      }
    } catch (error) {
      console.error("Error submitting payment:", error);
      setToast({
        type: "error",
        message: "Network error. Please check your connection and try again.",
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  const paymentBarcodeSrc =
    formData.amount === "100"
      ? "/assets/images/100.jpeg"
      : formData.amount === "50"
      ? "/assets/images/50.jpeg"
      : null;

  // When Step 2 opens, focus/scroll to the barcode section
  useEffect(() => {
    if (currentStep !== 2) return;
    if (!paymentBarcodeSrc) return;

    const t = window.setTimeout(() => {
      scrollToElement(paymentBarcodeRef.current);
    }, 150);

    return () => window.clearTimeout(t);
  }, [currentStep, paymentBarcodeSrc]);

  const effectiveRef =
    thankYouData?.reference_number || referenceNumber || undefined;

  return (
    <>
      {/* ================= ENHANCED FORM ================= */}
      <section className="bg-gray-50 px-4 sm:px-6 lg:px-[110px] py-6 sm:py-8 mt-16 md:mt-[79px]">
        <h1
          className="text-3xl sm:text-4xl lg:text-5xl font-bold mb-5"
          style={{ fontFamily: "Georgia, serif" }}
        >
          {showThankYou ? "Thank You" : "Membership Application"}
        </h1>
        <div className="mx-auto max-w-6xl space-y-6 sm:space-y-8">
          {showThankYou ? (
            <ThankYouSection
              referenceNumber={effectiveRef}
              data={thankYouData}
              onRefresh={
                effectiveRef ? () => loadStatus(effectiveRef) : undefined
              }
              onNewApplication={resetFlow}
            />
          ) : (
            <>
              {/* Progress Indicator */}
              <div className="bg-white rounded-xl p-4 sm:p-6 shadow-sm">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 sm:gap-0">
                  <div className="flex items-center gap-2">
                    <div className="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                      <span className="text-white font-semibold">1</span>
                    </div>
                    <span className="font-medium text-gray-700">
                      Personal Info
                    </span>
                  </div>

                  <div className="hidden sm:block h-1 w-10 md:w-24 bg-gray-200"></div>

                  <div className="flex items-center gap-2">
                    <div
                      className={`w-8 h-8 rounded-full flex items-center justify-center ${
                        applicantType ? "bg-blue-600" : "bg-gray-200"
                      }`}
                    >
                      <span
                        className={`font-semibold ${
                          applicantType ? "text-white" : "text-gray-500"
                        }`}
                      >
                        2
                      </span>
                    </div>
                    <span
                      className={`font-medium ${
                        applicantType ? "text-gray-700" : "text-gray-400"
                      }`}
                    >
                      Details
                    </span>
                  </div>

                  <div className="hidden sm:block h-1 w-10 md:w-24 bg-gray-200"></div>

                  <div className="flex items-center gap-2">
                    <div
                      className={`w-8 h-8 rounded-full flex items-center justify-center ${
                        applicantType ? "bg-blue-600" : "bg-gray-200"
                      }`}
                    >
                      <span
                        className={`font-semibold ${
                          applicantType ? "text-white" : "text-gray-500"
                        }`}
                      >
                        3
                      </span>
                    </div>
                    <span
                      className={`font-medium ${
                        applicantType ? "text-gray-700" : "text-gray-400"
                      }`}
                    >
                      Documents
                    </span>
                  </div>
                </div>
              </div>

              <form ref={formRef} onSubmit={handleSubmit} className="space-y-5">
                {currentStep === 1 && (
                  <>
            {/* PERSONAL INFORMATION */}
            <div className="rounded-xl bg-white p-5 sm:p-8 shadow-md border border-gray-100">
              <h2 className="mb-6 sm:mb-8 pb-2 sm:pb-3 border-b text-xl sm:text-2xl font-bold text-gray-800">
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
                  <div className="flex flex-col sm:flex-row sm:flex-wrap gap-4 sm:gap-8 mt-2">
                    {["Male", "Female", "Transgender"].map((option) => (
                      <Radio
                        key={option}
                        name="gender"
                        label={option}
                        checked={formData.gender === option}
                        onChange={() =>
                          setFormData((prev) => ({ ...prev, gender: option }))
                        }
                      />
                    ))}
                  </div>
                </div>
              </div>
            </div>

            {/* ADDRESS */}
            <div className="rounded-xl bg-white p-5 sm:p-8 shadow-md border border-gray-100">
              <h2 className="mb-6 sm:mb-8 pb-2 sm:pb-3 border-b text-xl sm:text-2xl font-bold text-gray-800">
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
                    <div className="w-full">
                      <Label>State</Label>
                      <div className="relative">
                        <select
                          name="state"
                          value={formData.state}
                          onChange={handleChange}
                          className="w-full px-4 py-3 bg-white border border-gray-300 yfocus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none appearance-none transition-colors"
                        >
                          <option value="">Select state</option>
                          <option value="AN">Andaman and Nicobar Islands</option>
                          <option value="AP">Andhra Pradesh</option>
                          <option value="AR">Arunachal Pradesh</option>
                          <option value="AS">Assam</option>
                          <option value="BR">Bihar</option>
                          <option value="CH">Chandigarh</option>
                          <option value="CG">Chhattisgarh</option>
                          <option value="DN">Dadra and Nagar Haveli and Daman and Diu</option>
                          <option value="DL">Delhi</option>
                          <option value="GA">Goa</option>
                          <option value="GJ">Gujarat</option>
                          <option value="HR">Haryana</option>
                          <option value="HP">Himachal Pradesh</option>
                          <option value="JK">Jammu and Kashmir</option>
                          <option value="JH">Jharkhand</option>
                          <option value="KA">Karnataka</option>
                          <option value="KL">Kerala</option>
                          <option value="LA">Ladakh</option>
                          <option value="LD">Lakshadweep</option>
                          <option value="MP">Madhya Pradesh</option>
                          <option value="MH">Maharashtra</option>
                          <option value="MN">Manipur</option>
                          <option value="ML">Meghalaya</option>
                          <option value="MZ">Mizoram</option>
                          <option value="NL">Nagaland</option>
                          <option value="OR">Odisha</option>
                          <option value="PB">Punjab</option>
                          <option value="RJ">Rajasthan</option>
                          <option value="SK">Sikkim</option>
                          <option value="TN">Tamil Nadu</option>
                          <option value="TS">Telangana</option>
                          <option value="TR">Tripura</option>
                          <option value="UP">Uttar Pradesh</option>
                          <option value="UK">Uttarakhand</option>
                          <option value="WB">West Bengal</option>
                        </select>
                        <div className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            className="h-5 w-5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                          >
                            <path
                              fillRule="evenodd"
                              d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                              clipRule="evenodd"
                            />
                          </svg>
                        </div>
                      </div>
                    </div>
              </div>
            </div>

            {/* CONTACT */}
            <div className="rounded-xl bg-white p-5 sm:p-8 shadow-md border border-gray-100">
              <h2 className="mb-6 sm:mb-8 pb-2 sm:pb-3 border-b text-xl sm:text-2xl font-bold text-gray-800">
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
                  <div className="flex flex-col sm:flex-row sm:flex-wrap gap-4 sm:gap-8 mt-2">
                    {["Indian", "Others"].map((option) => (
                      <Radio
                        key={option}
                        name="nationality"
                        label={option}
                        checked={formData.nationality === option}
                        onChange={() =>
                          setFormData((prev) => ({
                            ...prev,
                            nationality: option,
                          }))
                        }
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
            <div className="rounded-xl bg-white p-5 sm:p-8 shadow-md border border-gray-100">
              <h2 className="mb-6 sm:mb-8 pb-2 sm:pb-3 border-b text-xl sm:text-2xl font-bold text-gray-800">
                Educational Details
              </h2>

              <div className="space-y-6">
                <div>
                  <Label>Educational Qualification</Label>
                  <div className="flex flex-col sm:flex-row sm:flex-wrap gap-4 sm:gap-8 mt-2">
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
                  <div className="flex flex-col sm:flex-row sm:flex-wrap gap-4 sm:gap-8 mt-2">
                    {["Pursuing", "Completed"].map((option) => (
                      <Radio
                        key={option}
                        name="education_status"
                        label={option}
                        checked={formData.education_status === option}
                        onChange={() => {
                          setFormData((prev) => ({
                            ...prev,
                            education_status: option,
                          }));
                          // Auto-select applicant type based on education status
                          if (option === "Pursuing") {
                            setApplicantType("student");
                          } else if (option === "Completed") {
                            setApplicantType("professional");
                          }
                        }}
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
            <div className="rounded-xl bg-white p-5 sm:p-8 shadow-md border border-gray-100">
              <h2 className="mb-6 sm:mb-8 pb-2 sm:pb-3 border-b text-xl sm:text-2xl font-bold text-gray-800">
                Applicant Details
              </h2>

              <div className="grid gap-6 md:grid-cols-2">
                <div>
                  <label className="block text-sm font-semibold text-gray-700 mb-2">
                    Applicant Type {formData.education_status && <span className="text-sm font-normal text-gray-500">(Auto-selected)</span>}
                  </label>
                  <div className="relative">
                    <div className={`absolute left-4 top-1/2 transform -translate-y-1/2 ${formData.education_status ? "text-gray-300" : "text-gray-400"}`}>
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        className="h-5 w-5"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                      >
                        <path
                          fillRule="evenodd"
                          d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                          clipRule="evenodd"
                        />
                      </svg>
                    </div>
                    <select
                      name="applicant_type"
                      className={`w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg outline-none transition-colors ${
                        formData.education_status 
                          ? "bg-gray-100 text-gray-500 cursor-not-allowed" 
                          : "bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      }`}
                      value={applicantType}
                      onChange={(e) => {
                        const v = e.target.value;
                        setApplicantType(
                          v === "student" || v === "professional" ? v : ""
                        );
                      }}
                      disabled={!!formData.education_status}
                    >
                      <option value="">Select applicant type</option>
                      <option value="student">Student</option>
                      <option value="professional">Professional</option>
                    </select>
                    <div className={`absolute right-4 top-1/2 transform -translate-y-1/2 pointer-events-none ${formData.education_status ? "text-gray-300" : "text-gray-400"}`}>
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        className="h-5 w-5"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                      >
                        <path
                          fillRule="evenodd"
                          d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                          clipRule="evenodd"
                        />
                      </svg>
                    </div>
                  </div>
                  {formData.education_status && (
                    <p className="text-sm text-blue-600 mt-2">
                      {formData.education_status === "Pursuing" 
                        ? "✓ Student membership selected (₹50/year)" 
                        : "✓ Professional membership selected (₹100/year)"}
                    </p>
                  )}
                </div>

                <div>
                  <Label>Membership Fee</Label>
                  <div className="relative">
                    <div className="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-700 font-medium flex items-center h-[44px]">
                      ₹
                    </div>
                    <input
                      value={membershipFee ? `₹${membershipFee}` : ""}
                      disabled
                      className="w-full pl-12  h-[44px]  bg-gray-50 border border-gray-300 rounded-lg font-semibold text-gray-700"
                    />
                  </div>
                </div>
              </div>
            </div>

            {/* EMPLOYMENT */}
            {applicantType === "professional" && (
              <div className="rounded-xl bg-white p-5 sm:p-8 shadow-md border border-gray-100">
                <h2 className="mb-6 sm:mb-8 pb-2 sm:pb-3 border-b text-xl sm:text-2xl font-bold text-gray-800">
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
            <div className="rounded-xl bg-white p-5 sm:p-8 shadow-md border border-gray-100">
              <h2 className="mb-6 sm:mb-8 pb-2 sm:pb-3 border-b text-xl sm:text-2xl font-bold text-gray-800">
                Document Upload
              </h2>

              <div className="space-y-6 sm:space-y-8">
                {/* Photo Upload */}
                <div className="space-y-2">
                  <Label>Recent Passport Size Photo *</Label>
                  <FileUpload
                    id="file-upload-photo"
                    ref={fileInputRefs.photo}
                    onChange={(e) => handleFileChange("photo", e)}
                    acceptedTypes="image/*"
                    fileName={files.photo?.name}
                    error={errors.photo}
                  />
                </div>

                {/* ID Proof Upload */}
                <div className="space-y-2">
                  <Label>ID Card / Registration Certificate *</Label>
                  <FileUpload
                    id="file-upload-id-proof"
                    ref={fileInputRefs.id_proof}
                    onChange={(e) => handleFileChange("id_proof", e)}
                    acceptedTypes=".pdf,.jpg,.jpeg,.png"
                    fileName={files.id_proof?.name}
                    error={errors.id_proof}
                  />
                </div>

                {/* Student ID (for students) */}
                {/* {applicantType === "student" && (
                  <div className="space-y-2">
                    <Label>Student ID Card *</Label>
                    <FileUpload
                      ref={fileInputRefs.student_id}
                      onChange={(e) => handleFileChange("student_id", e)}
                      acceptedTypes=".pdf,.jpg,.jpeg,.png"
                      fileName={files.student_id?.name}
                    />
                  </div>
                )} */}

                {/* Employment Proof (for employees) */}
                {/* {applicantType === "professional" && (
                  <div className="space-y-2">
                    <Label>Employment Proof (Self Attested) (Optional)</Label>
                    <FileUpload
                      id="file-upload-employment-proof"
                      ref={fileInputRefs.employment_proof}
                      onChange={(e) => handleFileChange("employment_proof", e)}
                      acceptedTypes=".pdf,.jpg,.jpeg,.png"
                      fileName={files.employment_proof?.name}
                    />
                  </div>
                )} */}
              </div>
            </div>
              </>
            )}

            {/* STEP 1: TERMS AND SUBMIT */}
            {currentStep === 1 ? (
              <div className="bg-white rounded-xl p-5 sm:p-8 shadow-md border border-gray-100">
                <div className="space-y-6 sm:space-y-8">
                  <div
                    className="p-6 bg-blue-50 rounded-lg border border-blue-100"
                      ref={termsRef}
                  >
                    <label className="flex items-start gap-3 cursor-pointer">
                      <input
                        type="checkbox"
                        checked={acceptedTerms}
                        onChange={(e) => {
                          setAcceptedTerms(e.target.checked);
                          if (errors.terms) {
                            setErrors((prev) => ({ ...prev, terms: "" }));
                          }
                        }}
                        className="mt-1 w-5 h-5 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500"
                      />
                      <span className="text-gray-700">
                        I hereby declare that the information provided above is
                        true and correct to the best of my knowledge. I agree to
                        abide by the rules and regulations of the association. *
                      </span>
                    </label>
                    {errors.terms && (
                      <p className="text-red-500 text-sm mt-2">
                        {errors.terms}
                      </p>
                    )}
                  </div>

                  <div className="text-center">
                    <button
                      type="submit"
                      disabled={isSubmitting}
                      className={`w-full sm:w-auto px-8 sm:px-16 py-4 font-semibold rounded-full border-2 border-solid border-black transition ${
                        isSubmitting
                          ? "opacity-60 cursor-not-allowed"
                          : "hover:opacity-90"
                      }`}
                    >
                      {isSubmitting ? "Submitting..." : "Submit Application"}
                    </button>
                    <p className="mt-4 text-sm text-gray-500">
                      * Required fields: Name, Email, Mobile Number, Photo,
                      Education Document, and Terms Acceptance
                    </p>
                  </div>
                </div>
              </div>
            ) : (
              <div className="bg-white rounded-xl p-6 shadow-md border border-gray-100">
                <div className="flex flex-col gap-2 text-center">
                  <p className="text-sm font-semibold text-green-700">
                    Step 1 completed
                  </p>
                  <p className="text-sm text-gray-600">
                    We sent a payment QR to your email. Complete payment and
                    submit the details below.
                  </p>
                  {/* {referenceNumber && (
                    <p className="text-sm text-gray-700">
                      Reference Number:{" "}
                      <span className="font-semibold">{referenceNumber}</span>
                    </p>
                  )} */}
                </div>
              </div>
            )}

            {/* STEP 2: PAYMENT DETAILS */}
            {currentStep === 2 ? (
              <div className="rounded-xl bg-white p-5 sm:p-8 shadow-md border border-gray-100">
                <div className="flex flex-col items-center text-center gap-3 mb-6">
                  <h2 className="text-xl sm:text-2xl font-bold text-gray-800">
                    Payment Confirmation
                  </h2>
                  <div
                    className={`w-20 h-20 sm:w-24 sm:h-24 rounded-full flex items-center justify-center text-base sm:text-lg font-semibold shadow-inner border ${
                      paymentTimer > 0
                        ? "border-green-300 bg-green-50 text-green-700"
                        : "border-red-300 bg-red-50 text-red-700"
                    }`}
                  >
                    {paymentTimer > 0
                      ? formatTime(paymentTimer)
                      : "Expired"}
                  </div>
                </div>

                {paymentBarcodeSrc && (
                  <div className="mb-8">
                    <div
                      ref={paymentBarcodeRef}
                      tabIndex={-1}
                      className="rounded-lg border border-solid border-gray-300 bg-gray-50 p-4 flex flex-col items-center gap-3"
                    >
                      <p className="text-sm text-gray-700 font-medium">
                        Scan to pay (₹{formData.amount})
                      </p>
                      <img
                        src={paymentBarcodeSrc}
                        alt="Payment QR"
                        className="w-48 h-48 sm:w-56 sm:h-56 object-contain rounded-md shadow-sm"
                      />
                    </div>
                  </div>
                )}

                <div className="grid gap-6 md:grid-cols-2">
                  <input type="hidden" name="record_id" value={recordId} />
                  <input
                    type="hidden"
                    name="selected_amount"
                    value={formData.amount}
                  />
                  <div className="md:col-span-2">
                    <Input
                      label="Transaction ID"
                      name="payment_transaction_id"
                      value={formData.payment_transaction_id}
                      onChange={handleChange}
                      placeholder="Enter Transaction ID"
                      error={errors.payment_transaction_id}
                      ref={paymentTransactionRef}
                    />
                  </div>
                  <div className="md:col-span-2">
                    <div className="p-4 bg-blue-50 border border-blue-100 rounded-lg text-sm text-gray-700 flex items-center justify-center text-center">
                      OR
                    </div>
                  </div>
                </div>

                <div className="mt-6 space-y-2" ref={paymentProofSectionRef}>
                  <Label>Upload Screenshot (Image/PDF)</Label>
                  <FileUpload
                    id="file-upload-payment-proof"
                    ref={fileInputRefs.payment_proof}
                    onChange={(e) => handleFileChange("payment_proof", e)}
                    acceptedTypes=".pdf,.jpg,.jpeg,.png"
                    fileName={files.payment_proof?.name}
                    error={errors.payment_proof}
                  />
                </div>

                <div className="text-center mt-8">
                    <button
                      type="submit"
                      disabled={isSubmitting || isPaymentExpired}
                      className={`w-full sm:w-auto px-8 sm:px-16 py-4 font-semibold rounded-full border-2 border-solid border-black transition ${
                        isSubmitting || isPaymentExpired
                          ? "opacity-60 cursor-not-allowed"
                          : "hover:opacity-90"
                      }`}
                    >
                    {isSubmitting ? "Submitting..." : "Submit Payment"}
                  </button>
                  <p className="mt-4 text-sm text-gray-500">
                    * Provide Transaction ID or Screenshot to confirm payment.
                  </p>
                </div>
              </div>
            ) : (
              <div className="rounded-xl bg-white p-6 shadow-md border border-gray-100 text-sm text-gray-600 text-center">
                Complete Step 1 to unlock the payment section.
              </div>
            )}
              </form>
            </>
          )}
        </div>
      </section>

    </>
  );
}

/* ================= ENHANCED REUSABLE UI ================= */

function ThankYouSection({
  referenceNumber,
  data,
  onRefresh,
  onNewApplication,
}: {
  referenceNumber?: string;
  data: {
    reference_number?: string;
    membership_id?: string;
    payment_status?: string;
    status?: string;
    download_url?: string;
    message?: string;
  } | null;
  onRefresh?: () => void | Promise<void>;
  onNewApplication: () => void;
}) {
  return (
    <div className="bg-white rounded-xl p-6 sm:p-10 shadow-md border border-gray-100">
      <div className="flex flex-col items-center text-center gap-4">
        <div className="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-green-100 flex items-center justify-center">
          <svg
            viewBox="0 0 24 24"
            className="w-8 h-8 sm:w-10 sm:h-10 text-green-700"
            fill="none"
            stroke="currentColor"
            strokeWidth={2.5}
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              d="M20 6L9 17l-5-5"
            />
          </svg>
        </div>

        <h2 className="text-2xl sm:text-3xl font-bold text-gray-900">
          Thank you for your submission
        </h2>
        <p className="text-gray-600 max-w-2xl">
          We have received your membership application. Keep your reference
          number for tracking.
        </p>

        {referenceNumber ? (
          <div className="mt-2 rounded-lg bg-gray-50 border border-gray-200 px-6 py-4 w-full max-w-2xl">
            <p className="text-sm text-gray-600">Reference Number</p>
            <p className="text-xl font-semibold text-gray-900 break-all">
              {referenceNumber}
            </p>
          </div>
        ) : (
          <div className="mt-2 rounded-lg bg-yellow-50 border border-yellow-200 px-6 py-4 w-full max-w-2xl">
            <p className="text-sm text-yellow-900">
              Reference number not available. Please check your email/SMS or
              try refreshing status.
            </p>
          </div>
        )}

        <div className="mt-4 grid gap-3 w-full max-w-2xl">
          {data?.message && (
            <div className="rounded-lg bg-yellow-50 border border-yellow-200 px-5 py-4 text-sm text-yellow-900 text-left">
              {data.message}
              <div className="mt-2 text-yellow-900/80">
                Tip: make sure the reference number exists in your database, then
                click “Refresh Status”.
              </div>
            </div>
          )}
          <StatusRow label="Application Status" value={data?.status} />
          <StatusRow label="Payment Status" value={data?.payment_status} />
          <StatusRow label="Membership ID" value={data?.membership_id} />
        </div>

        <div className="mt-8 flex flex-col sm:flex-row flex-wrap gap-3 justify-center w-full max-w-2xl">
          {onRefresh && (
            <button
              type="button"
              onClick={onRefresh}
              className="w-full sm:w-auto px-6 py-3 font-semibold rounded-full border-2 border-solid border-black transition hover:opacity-90"
            >
              Refresh Status
            </button>
          )}
          {data?.download_url && (
            <a
              href={data.download_url}
              target="_blank"
              rel="noreferrer"
              className="w-full sm:w-auto px-6 py-3 font-semibold rounded-full border-2 border-solid border-black transition hover:opacity-90 text-center"
            >
              Download
            </a>
          )}
          <button
            type="button"
            onClick={onNewApplication}
            className="w-full sm:w-auto px-6 py-3 font-semibold rounded-full border-2 border-solid border-black transition hover:opacity-90"
          >
            New Application
          </button>
        </div>
      </div>
    </div>
  );
}

function StatusRow({ label, value }: { label: string; value?: string }) {
  return (
    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-4 rounded-lg border border-gray-200 bg-white px-5 py-4">
      <span className="text-sm font-semibold text-gray-700">{label}</span>
      <span className="text-sm text-gray-900 sm:text-right break-words">
        {value || "—"}
      </span>
    </div>
  );
}

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
          className={`w-full px-4 py-3 text-gray-900 bg-white border  focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors placeholder-gray-400 ${
            error ? "border-red-500" : "border-gray-300"
          }`}
        />
        {error && <p className="text-red-500 text-sm mt-1">{error}</p>}
      </div>
    );
  }
);

Input.displayName = "Input";

interface TextareaProps
  extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
  label: string;
  error?: string;
}

function Textarea({ label, error, ...props }: TextareaProps) {
  return (
    <div className="w-full">
      <Label>{label}</Label>
      <textarea
        {...props}
        className={`w-full px-4 py-3 text-gray-900 bg-white border focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors resize-none placeholder-gray-400 ${
          error ? "border-red-500" : "border-gray-300"
        }`}
      />
      {error && <p className="text-red-500 text-sm mt-1">{error}</p>}
    </div>
  );
}

interface SelectInputProps
  extends React.SelectHTMLAttributes<HTMLSelectElement> {
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
          className="w-full pl-4 pr-10 py-3 bg-white border border-gray-300 r focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none appearance-none transition-colors"
        >
          <option value="">
            Select {label.toLowerCase().replace("*", "").trim()}
          </option>
          {options.map((option: string) => (
            <option key={option} value={option}>
              {option}
            </option>
          ))}
        </select>
        <div className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            className="h-5 w-5"
            viewBox="0 0 20 20"
            fill="currentColor"
          >
            <path
              fillRule="evenodd"
              d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
              clipRule="evenodd"
            />
          </svg>
        </div>
      </div>
    </div>
  );
}

function Radio({
  name,
  label,
  checked,
  onChange,
}: {
  name: string;
  label: string;
  checked: boolean;
  onChange: () => void;
}) {
  return (
    <label className="flex items-center gap-3 cursor-pointer">
      <div className="relative">
        <input
          type="radio"
          name={name}
          checked={checked}
          onChange={() => onChange()}
          className="sr-only peer"
        />
        <div className="w-5 h-5 border-2 border-gray-300 rounded-full flex items-center justify-center peer-checked:border-blue-600 transition-colors">
          {checked && (
            <div className="w-2.5 h-2.5 bg-blue-600 rounded-full"></div>
          )}
        </div>
      </div>
      <span className="text-gray-700">{label}</span>
    </label>
  );
}

function Checkbox({
  label,
  checked,
  onChange,
}: {
  label: string;
  checked: boolean;
  onChange: () => void;
}) {
  return (
    <label className="flex items-center gap-3 cursor-pointer">
      <div className="relative">
        <input
          type="checkbox"
          checked={checked}
          onChange={() => onChange()}
          className="sr-only peer"
        />
        <div className="w-5 h-5 border-2 border-gray-300 rounded flex items-center justify-center peer-checked:bg-blue-600 peer-checked:border-blue-600 transition-colors">
          {checked && (
            <svg
              className="w-3 h-3 text-white"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={3}
                d="M5 13l4 4L19 7"
              />
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
  id: string;
}

const FileUpload = React.forwardRef<HTMLInputElement, FileUploadProps>(
  ({ onChange, acceptedTypes, fileName, error, id }, ref) => {
    return (
      <div>
        <div
          className={`border-2 border-solid rounded-lg p-6 sm:p-8 text-center transition-colors duration-300 ${
            error
              ? "border-red-500 bg-red-50"
              : fileName
              ? "border-green-500 bg-green-50"
              : "border-gray-300 hover:border-blue-500"
          }`}
        >
          <div className="mb-4">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className={`h-10 w-10 sm:h-12 sm:w-12 mx-auto ${error ? "text-red-400" : fileName ? "text-green-500" : "text-gray-400"}`}
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={1.5}
                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
              />
            </svg>
          </div>
          <label htmlFor={id} className="cursor-pointer">
            <p className="text-gray-600 mb-2">
              <span className="text-blue-600 font-medium hover:underline">
                Click to upload
              </span>{" "}
              or drag and drop
            </p>
            <p className="text-sm text-gray-500 mb-3">
              {acceptedTypes === "image/*"
                ? "JPG, PNG up to 5MB"
                : "PDF, JPG, PNG up to 5MB"}
            </p>
            {fileName && (
              <p className="text-sm text-green-600 font-medium">
                ✓ Selected: {fileName}
              </p>
            )}
          </label>
          <input
            ref={ref}
            type="file"
            className="hidden"
            id={id}
            onChange={onChange}
            accept={acceptedTypes}
          />
        </div>
        {error && <p className="text-red-500 text-sm mt-2">{error}</p>}
      </div>
    );
  }
);

FileUpload.displayName = "FileUpload";
