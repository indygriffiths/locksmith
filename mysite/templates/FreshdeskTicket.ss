<p style="margin-bottom:10px">The certificate for $Domain.Domain is soon expiring at {$Certificate.ValidTo}. This ticket will escalate in priority until a new certificate is detected. Once detected this ticket will automatically resolve itself.</p>

<% if $Domain.Source != "Manual" %>
    <p style="margin-bottom:10px">This domain was added from $Domain.Source, so the certificate may be renewed automatically with {$Domain.Source}.</p>
<% end_if %>

<% if $Certificate.IsLetsEncrypt %>
    <p style="margin-bottom:10px">The current certificate was issued by Let's Encrypt, so the certificate may be renewed automatically.</p>
<% end_if %>

<h3 style="margin-bottom:10px">Certificate Information</h3>
<ul>
    <li>Certificate: $Certificate.Name</li>
    <li>Issuer: $Certificate.Issuer</li>
    <li>Expiration: $Certificate.ValidTo</li>
    <li>Serial: $Certificate.Serial</li>
    <li>Fingerprint: $Certificate.Fingerprint</li>
</ul>
