"use client";
import { useState } from "react";

export default function Membership() {
  const [applicantType, setApplicantType] = useState<
    "student" | "employee" | ""
  >("");
  const [gender, setGender] = useState<string>("");
  const [nationality, setNationality] = useState<string>("");
  const [status, setStatus] = useState<string>("");
  const [educationalQualification, setEducationalQualification] = useState<string[]>([]);

  const membershipFee =
    applicantType === "student"
      ? "₹50"
      : applicantType === "employee"
      ? "₹100"
      : "";

  const handleQualificationChange = (qualification: string) => {
    setEducationalQualification(prev =>
      prev.includes(qualification)
        ? prev.filter(q => q !== qualification)
        : [...prev, qualification]
    );
  };

  return (
    <>
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

          {/* PERSONAL INFORMATION */}
          <div className="rounded-xl bg-white p-8 shadow-md border border-gray-100">
            <h2 className="mb-8 pb-3 border-b text-2xl font-bold text-gray-800">
              Personal Information
            </h2>

            <div className="grid gap-6 md:grid-cols-2">
              <Input label="Name (IN BLOCK LETTERS)" placeholder="Enter your full name" />
              <Input label="Father's Name" placeholder="Enter father's name" />
              <Input label="Date of Birth" type="date" />
              <Input label="Age" type="number" placeholder="Enter age" />

              <div className="md:col-span-2">
                <Label>Gender</Label>
                <div className="flex gap-8 mt-2">
                  {["Male", "Female", "Transgender"].map((option) => (
                    <Radio 
                      key={option}
                      name="gender" 
                      label={option} 
                      checked={gender === option.toLowerCase()}
                      onChange={() => setGender(option.toLowerCase())}
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
                <Textarea label="Address" rows={3} placeholder="Enter your complete address" />
              </div>
              <Input label="City" placeholder="Enter city" />
              <Input label="District" placeholder="Enter district" />
              <Input label="PIN Code" placeholder="Enter PIN code" />
              <Input label="State" placeholder="Enter state" />
            </div>
          </div>

          {/* CONTACT */}
          <div className="rounded-xl bg-white p-8 shadow-md border border-gray-100">
            <h2 className="mb-8 pb-3 border-b text-2xl font-bold text-gray-800">
              Contact Details
            </h2>

            <div className="grid gap-6 md:grid-cols-2">
              <Input label="Mobile Number" placeholder="Enter 10-digit mobile number" type="tel" />
              <div>
                <Label>Nationality</Label>
                <div className="flex gap-8 mt-2">
                  {["Indian", "Others"].map((option) => (
                    <Radio 
                      key={option}
                      name="nationality" 
                      label={option} 
                      checked={nationality === option.toLowerCase()}
                      onChange={() => setNationality(option.toLowerCase())}
                    />
                  ))}
                </div>
              </div>
              <Input label="Email Address" type="email" placeholder="Enter email address" />
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
                      checked={educationalQualification.includes(qual.toLowerCase())}
                      onChange={() => handleQualificationChange(qual.toLowerCase())}
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
                      name="status" 
                      label={option} 
                      checked={status === option.toLowerCase()}
                      onChange={() => setStatus(option.toLowerCase())}
                    />
                  ))}
                </div>
              </div>

              <Input label="Academic Session (only if pursuing)" placeholder="e.g., 2023-2027" />
              <Input label="College / Institution Name" placeholder="Enter institution name" />
              <Input label="University Name" placeholder="Enter university name" />
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
                    className="w-full pl-12 pr-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
                    value={applicantType}
                    onChange={(e) =>
                      setApplicantType(e.target.value as any)
                    }
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
                    value={membershipFee}
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
                <SelectInput label="Currently Employed" options={["Yes", "No"]} />
                <SelectInput label="Type of Employment" options={["Government", "Private"]} />
                <Input label="Hospital / Institute Name" placeholder="Enter organization name" />
                <Input label="Present Designation" placeholder="Enter designation" />
                <Input label="Employee ID (if any)" placeholder="Enter employee ID" />
              </div>
            </div>
          )}

          {/* DOCUMENT UPLOAD */}
          {applicantType && (
            <div className="rounded-xl bg-white p-8 shadow-md border border-gray-100">
              <h2 className="mb-8 pb-3 border-b text-2xl font-bold text-gray-800">
                Document Upload
              </h2>

              <div className="space-y-2">
                <Label>
                  {applicantType === "student"
                    ? "Student ID Card (Self Attested)"
                    : "Employment Proof (Self Attested)"}
                </Label>
                <div className="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-500 transition-colors duration-300">
                  <div className="mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                  </div>
                  <p className="text-gray-600 mb-2">
                    <span className="text-blue-600 font-medium cursor-pointer hover:underline">Click to upload</span> or drag and drop
                  </p>
                  <p className="text-sm text-gray-500">
                    PDF, JPG, PNG up to 5MB
                  </p>
                  <input
                    type="file"
                    className="hidden"
                    id="file-upload"
                  />
                </div>
              </div>
            </div>
          )}

          {/* TERMS AND SUBMIT */}
          <div className="bg-white rounded-xl p-8 shadow-md border border-gray-100">
            <div className="space-y-8">
              <div className="p-6 bg-blue-50 rounded-lg border border-blue-100">
                <label className="flex items-start gap-3 cursor-pointer">
                  <input 
                    type="checkbox" 
                    className="mt-1 w-5 h-5 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500" 
                  />
                  <span className="text-gray-700">
                    I hereby declare that the information provided above is true and correct to the best of my knowledge. I agree to abide by the rules and regulations of the association.
                  </span>
                </label>
              </div>

              <div className="text-center">
                <button className="px-16 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                  Submit Application
                </button>
                <p className="mt-4 text-sm text-gray-500">
                  Please review all information before submitting
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>
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

function Input({ label, ...props }: any) {
  return (
    <div className="w-full">
      <Label>{label}</Label>
      <input
        {...props}
        className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors placeholder-gray-400"
      />
    </div>
  );
}

function Textarea({ label, ...props }: any) {
  return (
    <div className="w-full">
      <Label>{label}</Label>
      <textarea
        {...props}
        className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors resize-none placeholder-gray-400"
      />
    </div>
  );
}

function SelectInput({ label, options }: any) {
  return (
    <div className="w-full">
      <Label>{label}</Label>
      <div className="relative">
        <select className="w-full pl-4 pr-10 py-3 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none appearance-none transition-colors">
          <option value="">Select {label.toLowerCase()}</option>
          {options.map((option: string) => (
            <option key={option} value={option.toLowerCase()}>
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