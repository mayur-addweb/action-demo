<?php
/**
 * SAML 2.0 remote IdP metadata for SimpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote
 */

$metadata['https://sso.vscpa.com/idp/shibboleth'] = array (
  'entityid' => 'https://sso.vscpa.com/idp/shibboleth',
  'description' =>
    array (
      'en' => 'VSCPA',
    ),
  'OrganizationName' =>
    array (
      'en' => 'VSCPA',
    ),
  'name' =>
    array (
      'en' => 'VSCPA',
    ),
  'OrganizationDisplayName' =>
    array (
      'en' => 'VSCPA',
    ),
  'url' =>
    array (
      'en' => 'https://sso.vscpa.com',
    ),
  'OrganizationURL' =>
    array (
      'en' => 'https://sso.vscpa.com',
    ),
  'contacts' =>
    array (
      0 =>
        array (
          'contactType' => 'technical',
          'givenName' => 'Admin',
          'surName' => 'Technologies',
          'emailAddress' =>
            array (
              0 => 'developers@unleashed-technologies.com ',
            ),
        ),
    ),
  'metadata-set' => 'saml20-idp-remote',
  'SingleSignOnService' =>
    array (
      0 =>
        array (
          'Binding' => 'urn:mace:shibboleth:2.0:profiles:AuthnRequest',
          'Location' => 'https://sso.vscpa.com/idp/profile/SAML2/Unsolicited/SSO',
        ),
      1 =>
        array (
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
          'Location' => 'https://sso.vscpa.com/idp/profile/SAML2/POST/SSO',
        ),
      2 =>
        array (
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign',
          'Location' => 'https://sso.vscpa.com/idp/profile/SAML2/POST-SimpleSign/SSO',
        ),
      3 =>
        array (
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
          'Location' => 'https://sso.vscpa.com/idp/profile/SAML2/Redirect/SSO',
        ),
    ),
  'SingleLogoutService' =>
    array (
      0 =>
        array (
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
          'Location' => 'https://sso.vscpa.com/idp/profile/SAML2/Redirect/SLO',
        ),
      1 =>
        array (
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
          'Location' => 'https://sso.vscpa.com/idp/profile/SAML2/POST/SLO',
        ),
      2 =>
        array (
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign',
          'Location' => 'https://sso.vscpa.com/idp/profile/SAML2/POST-SimpleSign/SLO',
        ),
      3 =>
        array (
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
          'Location' => 'https://sso.vscpa.com/idp/profile/SAML2/SOAP/SLO',
        ),
    ),
  'ArtifactResolutionService' =>
    array (
      0 =>
        array (
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
          'Location' => 'https://sso.vscpa.com/idp/profile/SAML2/SOAP/ArtifactResolution',
          'index' => 1,
        ),
    ),
  'NameIDFormats' =>
    array (
      0 => 'urn:mace:shibboleth:1.0:nameIdentifier',
      1 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
      2 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
    ),
  'keys' =>
    array (
      0 =>
        array (
          'encryption' => false,
          'signing' => true,
          'type' => 'X509Certificate',
          'X509Certificate' => '

MIIDwTCCAqmgAwIBAgIJALna7Z8PIwdLMA0GCSqGSIb3DQEBCwUAMHcxCzAJBgNV
BAYTAlVTMQswCQYDVQQIDAJWQTETMBEGA1UEBwwKR2xlbiBBbGxlbjEOMAwGA1UE
CgwFVlNDUEExFjAUBgNVBAMMDXNzby52c2NwYS5jb20xHjAcBgkqhkiG9w0BCQEW
D3ZzY3BhQHZzY3BhLmNvbTAeFw0xOTAyMTgxNzA1MzVaFw0zOTAyMTMxNzA1MzVa
MHcxCzAJBgNVBAYTAlVTMQswCQYDVQQIDAJWQTETMBEGA1UEBwwKR2xlbiBBbGxl
bjEOMAwGA1UECgwFVlNDUEExFjAUBgNVBAMMDXNzby52c2NwYS5jb20xHjAcBgkq
hkiG9w0BCQEWD3ZzY3BhQHZzY3BhLmNvbTCCASIwDQYJKoZIhvcNAQEBBQADggEP
ADCCAQoCggEBAPUIcz7FElZObbQkCAUDF3/Og8usTMMRAnZGTZQ+2Iqb4ADOjB5Z
7RkcRTxTgisjRsXbK344SSuWBERQCUHewlNo3LYMQGNLuZglLo649c3BWC+R1kCV
58SBZX1DmFsskvRSpZcA9k5nQFCNY9lKpBoFXlgpkTHeH6RFuT+k4wgW/wGvEWPL
ucY7WJt5R1uPsXKzg9+SLXy365J1PFrbHfeN2fQFKCshsWRey2djTENOk+mmIMPt
ZLVPgq7cjxoRCeDtqgcP+NOkKVzNHtC/8RgT6/zXMNy1wMVuwraFs3rhrhngtKgy
uL0cwhQMUpKQGE3ibyO3Yfu39A1ll6L0vlECAwEAAaNQME4wHQYDVR0OBBYEFMgU
KEgD4tg3CkL4wDemZ+HrMwDRMB8GA1UdIwQYMBaAFMgUKEgD4tg3CkL4wDemZ+Hr
MwDRMAwGA1UdEwQFMAMBAf8wDQYJKoZIhvcNAQELBQADggEBAMfHsA0e1twAC+0L
lDJfxrZb08uQHLJ6EUTkpGc9+TF5pI4OXJhFNKlnJSNwgT7c1+b+4KhmvMZVMdhO
Sxfun3KdY8Za+c4bHgEEs5eZwAIC0SBGRPmwbZu8cinGPy1vu5GNGEvEqIpAJc5p
t8PSHzst42pKC3C+tR03S7rAJ1IUqOrNPBTUvlEDRFOcNknGayW4pqMkoxmQCMtK
coaX+gpk8JdVxyJaFrUpq3a1FpwUokGEbFZVqf9Q05qplxGg+AOu/7p0fQqgI7k1
KZUnxzdMekAnpuIx0nLjUOxxEvSzv3wkZncTazzAc4jccEpLYiJ9CL0nkApxGVTy
UByhTrs=


                    ',
        ),
      1 =>
        array (
          'encryption' => true,
          'signing' => false,
          'type' => 'X509Certificate',
          'X509Certificate' => '

MIIDwTCCAqmgAwIBAgIJAOkQ2bl/WsrpMA0GCSqGSIb3DQEBCwUAMHcxCzAJBgNV
BAYTAlVTMQswCQYDVQQIDAJWQTETMBEGA1UEBwwKR2xlbiBBbGxlbjEOMAwGA1UE
CgwFVlNDUEExFjAUBgNVBAMMDXNzby52c2NwYS5jb20xHjAcBgkqhkiG9w0BCQEW
D3ZzY3BhQHZzY3BhLmNvbTAeFw0xOTAyMTgxNzA2MDVaFw0zOTAyMTMxNzA2MDVa
MHcxCzAJBgNVBAYTAlVTMQswCQYDVQQIDAJWQTETMBEGA1UEBwwKR2xlbiBBbGxl
bjEOMAwGA1UECgwFVlNDUEExFjAUBgNVBAMMDXNzby52c2NwYS5jb20xHjAcBgkq
hkiG9w0BCQEWD3ZzY3BhQHZzY3BhLmNvbTCCASIwDQYJKoZIhvcNAQEBBQADggEP
ADCCAQoCggEBAOWFUpTV2H1e2UfMz/MSyK0ggUQqSChS2cDRLw/9nWi78DM4Tn3k
5ZoGLFdJd5lQGtW+sHDGVPXUN7hC3dVJIhdji2sjdKzOlwC8PlbdyiYOBjEeHX22
5pLH1zx22npXCS8BLFf/0sjr99LtrfZQU3JGo2NrVBvp/ofc9GmJ52HmM9kGVrBP
HQRctxvkMQp7EJOAfp+qa997uU6l8HnrBc6WwbIA961mqpWCxyHI8W0dOnm67ngE
iVEVCpqMlm842K2S0bm5e918t8n2mC+HQh0dv55AJXTsdMyLUaGDagGk7xvAssqG
ZTLGVpANkQnVkX4u2lWKH3ZU7x+YN3w3b78CAwEAAaNQME4wHQYDVR0OBBYEFEM4
g4/8O26nxvm1hwHsEUFo39dBMB8GA1UdIwQYMBaAFEM4g4/8O26nxvm1hwHsEUFo
39dBMAwGA1UdEwQFMAMBAf8wDQYJKoZIhvcNAQELBQADggEBAIddiQ3W1M6HG5DY
2sJlRcBgs8dFeMWTBBmMJyfjwdEEcte4AUq3XyLPwhthoaJCYruiTFCAPq5nHgY4
1atmx8igVZsa6qMsnuxMRTeFX/2BxUvW4E5fuLumMhgjsdzaK1GvXDzI58B6XO1E
dgUHnkInHGcERtlN++whN/JedtwWVvMml4taTSR1gAYxqTOglKAfJFxjhCvaDHxd
6KiOPtnbS8JlvkQcod2Xcn6oKfunrbTT1awZGOpqXsimiUeiGMyD3RN06yIxY9Sk
aNryl6neOMrnAF6lHa3MTr1dvlw70F/M7RuuPiVmo/Lt/gft2hEhgsNzj/0+/35G
0SvQ/Go=


                    ',
        ),
    ),
  'scope' =>
    array (
      0 => 'sso.vscpa.com',
    ),
  'NameIDPolicy' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
);
