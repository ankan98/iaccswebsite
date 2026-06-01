"use client";
import { useState } from "react";

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

function buildPhpUrl(path) {
  const base = getPhpBaseUrl();
  const normalizedPath = path.startsWith("/") ? path : `/${path}`;
  return base ? `${base}${normalizedPath}` : normalizedPath;
}

export default function MembershipStatus() {
  const [formData, setFormData] = useState({
    membership_id: "",
    dob: "",
  });
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState(null);
  const [error, setError] = useState("");

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");
    setResult(null);

    const params = new URLSearchParams();
    const membershipId = formData.membership_id?.trim();
    const dob = formData.dob?.trim();
    if (membershipId) params.append("membership_id", membershipId);
    if (dob) params.append("dob", dob);

    if (!membershipId || !dob) {
      setError("Please enter your membership ID and date of birth.");
      return;
    }

    setLoading(true);
    try {
      const response = await fetch(
        `${buildPhpUrl("membership_status_check.php")}?${params.toString()}`,
        { method: "GET" }
      );
      const data = await response.json();
      if (!data.success) {
        setError(data.message || "Unable to fetch status. Please try again.");
        return;
      }
      setResult(data);
    } catch {
      setError("Network error. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  const status = result?.data?.status || "";
  const paymentStatus = result?.data?.payment_status || "";
  const normalizedStatus = status.trim().toLowerCase();
  const isApproved =
    normalizedStatus === "approved" || normalizedStatus === "approve";
  const normalizedPaymentStatus = paymentStatus.trim().toLowerCase();
  const isPaid = normalizedPaymentStatus === "paid";
  const downloadUrl =
    result?.download_url || result?.data?.download_url || "";

  return (
    <div className="bg-white">
      <section className="w-full bg-white flex flex-col gap-10 py-16 xl:px-[110px] md:px-20 px-5 mt-[40px]">
        <div>
          <h1
            className="w-full font-bold text-gray-900 xl:text-[48px] md:text-3xl text-2xl !leading-tight mb-3"
            style={{ fontFamily: "Georgia, serif" }}
          >
            Application Status Check
          </h1>
          <p className="text-gray-700 max-w-3xl">
            Enter your Membership ID / Reference ID and Date of Birth to check your status.
          </p>
        </div>

        <div className="bg-gray-50 border border-gray-200  p-6 md:p-8">
          <form onSubmit={handleSubmit} className="grid gap-6">
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">
               Membership ID / Reference ID
              </label>
              <input
                name="membership_id"
                value={formData.membership_id}
                onChange={handleChange}
                placeholder="Enter membership ID / Reference ID"
                className="w-full px-4 py-3 bg-white border border-gray-300  focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
              />
            </div>
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">
                Date of Birth
              </label>
              <input
                name="dob"
                type="date"
                value={formData.dob}
                onChange={handleChange}
                className="w-full px-4 py-3 bg-white border border-gray-300  focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
              />
            </div>

            <div className="text-center">
              <button
                type="submit"
                disabled={loading}
                className={`px-4 py-3 font-semibold rounded-full border-2 border-solid border-black transition ${
                  loading ? "opacity-60 cursor-not-allowed" : "hover:opacity-90"
                }`}
              >
                {loading ? "Checking..." : "Check Status"}
              </button>
            </div>
          </form>

          {error && (
            <div className="mt-6 text-center text-red-500 font-medium">
              {error}
            </div>
          )}
        </div>

        {result && (
          <div className="bg-white border-2 border-solid border-gray-400  p-6 md:p-8">
            <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
              <div>
                <h2 className="text-2xl font-semibold text-gray-900">
                  Status Result
                </h2>
                <p className="text-sm text-gray-600">
                  Reference: {result.data.reference_number || "N/A"}
                </p>
              </div>
              <div className="flex items-center gap-2">
    <span className="w-[180px] text-sm font-semibold text-gray-700">
      Membership Status:
    </span>
    <span
      className={`px-4 py-1 rounded-full text-xs font-semibold
        ${
          status === "Approved"
            ? "bg-green-50 text-green-700"
            : status === "Pending"
            ? "bg-yellow-50 text-yellow-700"
            : "bg-red-50 text-red-700"
        }`}
    >
      {status || "Unknown"}
    </span>
  </div>

  {/* Payment Status */}
  <div className="flex items-center gap-2">
    <span className="w-[180px] text-sm font-semibold text-gray-700">
      Payment Status:
    </span>
    <span
      className={`px-4 py-1 rounded-full text-xs font-semibold
        ${
          paymentStatus === "Paid"
            ? "bg-green-50 text-green-700"
            : paymentStatus === "Pending"
            ? "bg-yellow-50 text-yellow-700"
            : "bg-red-50 text-red-700"
        }`}
    >
      {paymentStatus || "Unpaid"}
    </span>
  </div>
            </div>

            <div className="grid gap-4 md:grid-cols-2 mt-6 text-gray-700">
              <div>
                <p>
                  <strong>Name:</strong> {result.data.name || "N/A"}
                </p>
                <p>
                  <strong>Membership ID:</strong>{" "}
                  {result.data.membership_id || "Not assigned"}
                </p>
              </div>
              <div>
                <p>
                  <strong>Email:</strong> {result.data.email || "N/A"}
                </p>
                <p>
                  <strong>Mobile:</strong> {result.data.mobile || "N/A"}
                </p>
              </div>
            </div>

            {isApproved && isPaid && downloadUrl && (
              <div className="text-center mt-8">
                <a
                  href={downloadUrl}
                  className="inline-block px-8 py-3  border-2 border-solid border-black rounded-full font-semibold"
                >
                  Download E-Certificate (PDF)
                </a>
              </div>
            )}
            {(!isApproved || !isPaid) && (
              <div className="text-center mt-8 text-sm text-red-400">
                {!isApproved
                  ? "E-Certificate download is available after approval."
                  : "Please complete your payment to download your E-certificate (PDF). If you have already paid, please ignore this message."}
              </div>
            )}
          </div>
        )}

      </section>
    </div>
  );
}
