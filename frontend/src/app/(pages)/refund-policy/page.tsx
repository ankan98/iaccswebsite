export default function RefundPolicy() {
  return (
    <div className="bg-white">
      <section className="w-full bg-white flex flex-col gap-10 py-16 xl:px-[110px] md:px-20 px-5 mt-[40px]">
        <div className="w-full">
          <h1
            className="w-full font-bold text-gray-900 xl:text-[48px] md:text-3xl text-2xl !leading-tight mb-3"
            style={{ fontFamily: "Georgia, serif" }}
          >
            Refund &amp; Cancellation Policy
          </h1>
          <p className="text-gray-700 max-w-3xl">
            Our policy regarding cancellations and refunds.
          </p>
        </div>

        <div className="w-full  mx-auto text-gray-700 leading-relaxed space-y-8">
          <div className="pb-6 border-b border-gray-200">
            <p className="text-gray-500 text-sm">
              <strong>Last Updated:</strong> January 7, 2026
            </p>
          </div>

          <p>
            At Association for Critical Care Sciences (ACCS), we value our
            members and strive to provide the best professional support. Please
            read our policy regarding cancellations and refunds below.
          </p>

          <div className="space-y-3">
            <h2 className="text-2xl font-bold text-gray-900">
              1. Membership Fees
            </h2>
            <ul className="list-disc ml-6 space-y-2">
              <li>
                <strong>Non-refundable:</strong> All membership fees (Student or
                Professional) are non-refundable once the payment is successful
                and the membership has been activated.
              </li>
              <li>
                <strong>Application rejection:</strong> In the rare event that
                a membership application is rejected by the ACCS committee due
                to eligibility issues, a full refund will be processed to the
                original payment source within 7–10 working days.
              </li>
            </ul>
          </div>

          <div className="space-y-3">
            <h2 className="text-2xl font-bold text-gray-900">
              2. Event/Workshop Registrations
            </h2>
            <ul className="list-disc ml-6 space-y-2">
              <li>
                For specific events or workshops, cancellation requests must be
                sent to{" "}
                <a
                  href="mailto:admin@iaccs.org.in"
                  className="text-blue-600 hover:underline"
                >
                  admin@iaccs.org.in
                </a>{" "}
                at least <strong>7 days prior</strong> to the event date to be
                eligible for a refund (minus processing fees).
              </li>
              <li>
                No refunds will be provided for cancellations made{" "}
                <strong>less than 7 days</strong> before an event or for{" "}
                <strong>&quot;no-shows&quot;</strong>.
              </li>
            </ul>
          </div>

          <div className="space-y-3">
            <h2 className="text-2xl font-bold text-gray-900">
              3. Duplicate Payments
            </h2>
            <p>
              In case of technical errors resulting in duplicate payments for
              the same service, the extra amount will be refunded to the
              original payment method after verification.
            </p>
          </div>

          <div className="space-y-3">
            <h2 className="text-2xl font-bold text-gray-900">
              4. Refund Process
            </h2>
            <ul className="list-disc ml-6 space-y-2">
              <li>
                <strong>Original payment method:</strong> All eligible refunds
                will be credited back to the original mode of payment (Credit
                Card, Debit Card, Net Banking, or UPI) used at the time of
                transaction.
              </li>
              <li>
                <strong>Processing time:</strong> The refund typically takes{" "}
                <strong>5 to 7 business days</strong> to reflect in your
                account, depending on your bank&apos;s processing time.
              </li>
            </ul>
          </div>

          <div className="space-y-3">
            <h2 className="text-2xl font-bold text-gray-900">5. Contact Us</h2>
            <p>
              For any issues related to payments or refunds, please contact us:
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

          <div className="space-y-3 pt-6 border-t border-gray-200">
            <h2 className="text-xl font-bold text-gray-900">Quick Summary</h2>
            <ul className="list-disc ml-6 space-y-1">
              <li>Membership fees are non-refundable after activation.</li>
              <li>
                Full refund for rejected applications (7–10 working days).
              </li>
              <li>Event cancellations: 7 days prior notice required.</li>
              <li>Duplicate payments will be refunded after verification.</li>
              <li>Refunds typically reflect in 5–7 business days.</li>
            </ul>
          </div>
        </div>
      </section>
    </div>
  );
}
