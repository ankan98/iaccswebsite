export default function PrivacyPolicy() {
  return (
    <div className="bg-white">
      <section className="w-full bg-white flex flex-col gap-10 py-16 xl:px-[110px] md:px-20 px-5 mt-[40px]">
        <div className="w-full">
          <h1
            className="w-full font-bold text-gray-900 xl:text-[48px] md:text-3xl text-2xl !leading-tight mb-3"
            style={{ fontFamily: "Georgia, serif" }}
          >
            Privacy Policy
          </h1>
          <p className="text-gray-700 max-w-3xl">
            Your privacy is important to us.
          </p>
        </div>

        <div className="w-full  mx-auto text-gray-700 leading-relaxed space-y-8">
          <div className="pb-6 border-b border-gray-200">
            <p className="text-gray-500 text-sm">
              <strong>Last Updated:</strong> January 7, 2026
            </p>
          </div>

          <div className="space-y-4">
            <p>
              Welcome to iaccs.org.in. We value your trust and are
              committed to protecting your personal information. This Privacy
              Policy outlines how we collect, use, and safeguard your data when
              you visit our website and use our services.
            </p>
            <p>
              By accessing or using this website, you agree to the terms of
              this Privacy Policy.
            </p>
          </div>

          <div className="space-y-3">
            <h2 className="text-2xl font-bold text-gray-900">
              1. Information We Collect
            </h2>
            <p>
              We collect only the information necessary to provide our services
              and verify your identity. This includes:
            </p>
            <ul className="list-disc ml-6 space-y-2">
              <li>
                <strong>Personal identification information:</strong> Name,
                email address, phone number, and postal address.
              </li>
              <li>
                <strong>Professional information:</strong> Medical registration
                details, clinic/hospital details, and professional
                qualifications (required for membership verification).
              </li>
              <li>
                <strong>Log data:</strong> IP address, browser type, and usage
                patterns.
              </li>
            </ul>
          </div>

          <div className="space-y-3">
            <h2 className="text-2xl font-bold text-gray-900">
              2. How We Use Your Information
            </h2>
            <p>The data collected is used strictly for the following purposes:</p>
            <ul className="list-disc ml-6 space-y-2">
              <li>
                <strong>Identity verification:</strong> To confirm your status
                as a medical professional or member.
              </li>
              <li>
                <strong>Communication:</strong> To send membership confirmations,
                administrative updates, newsletters, and payment receipts.
              </li>
              <li>
                <strong>Internal record keeping:</strong> To maintain an accurate
                registry of our users and members.
              </li>
              <li>
                <strong>Compliance:</strong> To meet legal and regulatory
                obligations.
              </li>
            </ul>
            <p>
              <strong>
                We do not sell, trade, or rent your personal identification
                information to third parties.
              </strong>
            </p>
          </div>

          <div className="space-y-3">
            <h2 className="text-2xl font-bold text-gray-900">
              3. Payment Processing &amp; Financial Data
            </h2>
            <p>
              To process payments (e.g., membership fees, event registrations),
              we use secure, third-party payment gateway providers.
            </p>
            <ul className="list-disc ml-6 space-y-2">
              <li>
                We do not store your credit card numbers, CVVs, expiry dates, or
                internet banking passwords on our servers.
              </li>
              <li>
                All payment transactions are processed through encrypted,
                PCI-DSS compliant infrastructure provided by our payment
                partners.
              </li>
              <li>
                Your payment is subject to the privacy policy and terms of the
                payment gateway provider.
              </li>
            </ul>
          </div>

          <div className="space-y-3">
            <h2 className="text-2xl font-bold text-gray-900">4. Data Security</h2>
            <p>
              We implement robust technical and organizational security measures
              to protect against unauthorized access, alteration, disclosure, or
              destruction of your personal data. This includes SSL encryption
              for data transmission and secure server hosting.
            </p>
          </div>

          <div className="space-y-3">
            <h2 className="text-2xl font-bold text-gray-900">5. Cookies</h2>
            <p>
              Our website may use &quot;cookies&quot; to enhance your browsing
              experience. Cookies help us understand user behavior and save your
              preferences. You can set your browser to refuse cookies, but
              please note that some parts of the site may not function properly
              as a result.
            </p>
          </div>

          <div className="space-y-3">
            <h2 className="text-2xl font-bold text-gray-900">
              6. Third-Party Links
            </h2>
            <p>
              Our website may contain links to external sites (e.g., medical
              journals, partner organizations). We do not control the privacy
              practices of these third-party sites and recommend you review
              their policies separately.
            </p>
          </div>

          <div className="space-y-3">
            <h2 className="text-2xl font-bold text-gray-900">
              7. Grievance Officer
            </h2>
            <p>
              In accordance with the Information Technology Act 2000 and rules
              made thereunder, the name and contact details of the Grievance
              Officer are provided below:
            </p>
            <p>
              <strong>Name:</strong> Bapan Sarkar
              <br />
              <strong>Title:</strong> Grievance Officer
              <br />
              <strong>Address:</strong> Mathkal, Nazrul Sarani, Dumdum
              Cantonment, Kolkata, 700065
              <br />
              <strong>Email:</strong>{" "}
              <a
                href="mailto:admin@iaccs.org.in"
                className="text-blue-600 hover:underline"
              >
                admin@iaccs.org.in
              </a>
            </p>
          </div>

          <div className="space-y-3">
            <h2 className="text-2xl font-bold text-gray-900">
              8. Changes to This Policy
            </h2>
            <p>
              We reserve the right to update this Privacy Policy at any time.
              When updated, the &quot;Last Updated&quot; date at the top of this
              page will be revised. We encourage you to review this page
              periodically.
            </p>
          </div>

          <div className="space-y-3 pt-6 border-t border-gray-200">
            <h2 className="text-2xl font-bold text-gray-900">9. Contact Us</h2>
            <p>
              If you have any questions regarding this policy or our data
              practices, please contact us:
            </p>
            <p>
              <strong>Email:</strong>{" "}
              <a
                href="mailto:admin@iaccs.org.in"
                className="text-blue-600 hover:underline"
              >
                admin@iaccs.org.in
              </a>
              <br />
              <strong>Address:</strong> Mathkal, Nazrul Sarani, Dumdum
              Cantonment, Kolkata, 700065
            </p>
          </div>
        </div>
      </section>
    </div>
  );
}
