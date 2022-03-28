<?php

namespace Drupal\am_net_membership\BillingClass;

use Drupal\user\UserInterface;

/**
 * The Billing Class Checker class.
 */
class DefaultBillingClassChecker implements BillingClassCheckerInterface {

  use BillingClassCodeTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'Default Billing Class Handler';
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return 'default_billing_class';
  }

  /**
   * {@inheritdoc}
   */
  public function getCode(UserInterface $user) {
    // See Billing Class decision tree:
    // https://unleashed.teamwork.com/#files/2204964.
    // A "null" value indicates a membership profile that has not been completed
    // (or a pending billing class determination).
    $billingClassCode = NULL;
    if ($this->isCollegeStudent($user)) {
      // I am a college student and not a licensed CPA or working in an
      // accounting or finance position.
      $undergraduate_date = $this->getUndergraduateDate($user);
      $undergraduate_date_in_the_future = (!empty($undergraduate_date)) ? (strtotime($undergraduate_date) > strtotime('now')) : FALSE;
      $graduate_date = $this->getGraduateDate($user);
      $graduate_date_in_the_future = (!empty($graduate_date)) ? (strtotime($graduate_date) > strtotime('now')) : FALSE;
      if ($undergraduate_date_in_the_future || $graduate_date_in_the_future) {
        // Billing code for undergraduate date or graduate date either
        // in the future.
        $billingClassCode = 30;
      }
      else {
        // A "-1" value indicates a bad date for a student membership selection.
        $billingClassCode = -1;
      }
    }
    elseif ($this->isCertifiedPublicAccountant($user)) {
      // I am a licensed CPA.
      // A "-2" value indicates a bad condition for a CPA membership selection.
      // NOT A CURRENT ISSUE.
      $billingClassCode = -2;
      $cert_va_no_is_filled = $this->hasVirginiaCertificationNumber($user);
      $is_home_address_in_virginia = $this->isHomeAddressInVirginia($user);
      $is_work_address_in_virginia = $this->isWorkAddressInVirginia($user);
      if (!($cert_va_no_is_filled && ($is_home_address_in_virginia || $is_work_address_in_virginia))) {
        // Billing class "11" and the related logic check may be removed.
        // Please make note of this in the code for this module.
        $billingClassCode = 11;
      }
      else {
        if ($this->isEducator($user)) {
          // Billing Code for user's job position: Educator.
          $billingClassCode = 13;
        }
        else {
          // Not "Educator".
          $reference_time = strtotime('first day of -36 month');
          $virginia_certification_original_date = $this->getOriginalDateOfVirginiaCertification($user);
          $out_of_state_certification_original_date = $this->getOriginalDateOfOutOfStateCertification($user);
          $virginia_certification_original_date_lte = (!empty($virginia_certification_original_date)) ? (strtotime($virginia_certification_original_date) >= $reference_time) : FALSE;
          $out_of_state_certification_original_date_lte = (!empty($out_of_state_certification_original_date)) ? (strtotime($out_of_state_certification_original_date) >= $reference_time) : FALSE;
          $meet_condition = FALSE;
          if ($this->isLicensedInAndOutOfState($user)) {
            $meet_condition = ($virginia_certification_original_date_lte && $out_of_state_certification_original_date_lte);
          }
          elseif ($this->isLicensedInOutOfStateOnly($user)) {
            $meet_condition = $out_of_state_certification_original_date_lte;
          }
          elseif ($this->isLicensedInStateOnly($user)) {
            $meet_condition = $virginia_certification_original_date_lte;
          }
          if ($meet_condition) {
            $billingClassCode = 19;
          }
          else {
            if ($this->isEmploymentStatusSeasonal($user) || $this->isEmploymentStatusPartTime($user) || $this->isEmploymentStatusRetired($user) || $this->isEmploymentStatusLeaveOfAbsence($user) || $this->isEmploymentStatusUnemployed($user)) {
              // Billing Class for professionals with Employment Status:
              // Seasonal, PartTime, Retired, Leave of Absence or Unemployed.
              $billingClassCode = 53;
            }
            else {
              $billingClassCode = 10;
            }
          }
        }
      }
    }
    elseif ($this->isUnlicensedProfessional($user)) {
      // I am an unlicensed Professional.
      // A "-3" value indicates a bad membership qualification value for a
      // professional membership selection.
      $billingClassCode = -3;
      if ($this->isPursuingCertifiedPublicAccountantLicense($user) || $this->isEmployedByaCertifiedPublicAccountant($user)) {
        $reference_time = strtotime('first day of -60 month');
        $undergraduate_date = $this->getUndergraduateDate($user);
        $undergraduate_date_lt_reference_time = (!empty($undergraduate_date)) ? (strtotime($undergraduate_date) > $reference_time) : FALSE;
        $undergraduate_date_gte_reference_time = (!empty($undergraduate_date)) ? (strtotime($undergraduate_date) <= $reference_time) : FALSE;
        if ($undergraduate_date_lt_reference_time) {
          // Billing Class for unlicensed professional with undergraduate date
          // less than 60 months ago.
          $billingClassCode = 21;
        }
        elseif ($undergraduate_date_gte_reference_time) {
          // Billing Class for unlicensed professional with undergraduate date
          // greater than or equal to 60 months ago.
          $billingClassCode = 20;
        }
      }
      else {
        if ($this->isEducator($user)) {
          // Billing Code for unlicensed Professional with the job position:
          // Educator.
          $billingClassCode = 23;
        }
        elseif ($this->isMembershipQualificationFirmAdministrator($user)) {
          // Billing Code for unlicensed Professional with the Membership
          // Qualify Firm Administrator.
          // The `field_member_qualify` does not actually grant Firm Admin role.
          $billingClassCode = 55;
        }
        else {
          if ($this->isEmploymentStatusFullTime($user) && ($this->isEmployedByaCertifiedPublicAccountant($user) || $this->isMembershipQualifyEmployedInAnAccountingPosition($user) || $this->isNonCertifiedPublicAccountantOwnerOfFirm($user))) {
            $billingClassCode = 54;
          }
          elseif ($this->isEmploymentStatusSeasonal($user) || $this->isEmploymentStatusPartTime($user) || $this->isEmploymentStatusRetired($user) || $this->isEmploymentStatusLeaveOfAbsence($user) || $this->isEmploymentStatusUnemployed($user)) {
            // Type > Mbr: Associate
            // UDF > Employment Status > Seasonal or Part-time, Retired, LOA
            // or Unemployed.
            $billingClassCode = 52;
          }
        }
      }
    }

    return $billingClassCode;
  }

  /**
   * {@inheritdoc}
   */
  public function getHelp() {
    $table = [
      '#type' => 'table',
      '#header' => ['Billing Class Code', 'Description'],
    ];
    $table[] = [
      'billing_class' => [
        '#type' => 'item',
        '#markup' => '10',
      ],
      'description' => [
        '#type' => 'item',
        '#markup' => 'Licensed in <strong>In-State Only</strong>.',
      ],
    ];
    $table[] = [
      'billing_class' => [
        '#type' => 'item',
        '#markup' => '11',
      ],
      'description' => [
        '#type' => 'item',
        '#markup' => 'Licensed in <strong>Out-Of-State Only</strong>.',
      ],
    ];
    $table[] = [
      'billing_class' => [
        '#type' => 'item',
        '#markup' => '13',
      ],
      'description' => [
        '#type' => 'item',
        '#markup' => 'Membership selection <strong>Licensed CPA</strong>.',
      ],
    ];
    $table[] = [
      'billing_class' => [
        '#type' => 'item',
        '#markup' => '23',
      ],
      'description' => [
        '#type' => 'item',
        '#markup' => 'Membership selection <strong>Unlicensed Professional</strong>.',
      ],
    ];
    $table[] = [
      'billing_class' => [
        '#type' => 'item',
        '#markup' => '30',
      ],
      'description' => [
        '#type' => 'item',
        '#markup' => 'Membership selection <strong>College student and not a licensed CPA or working in an accounting or finance position</strong>.',
      ],
    ];
    $table[] = [
      'billing_class' => [
        '#type' => 'item',
        '#markup' => '55',
      ],
      'description' => [
        '#type' => 'item',
        '#markup' => 'Membership Qualify <strong>Firm Administrator</strong>.',
      ],
    ];
    $table[] = [
      'billing_class' => [
        '#type' => 'item',
        '#markup' => '20',
      ],
      'description' => [
        '#type' => 'item',
        '#markup' => 'Membership Qualify <strong>Pursuing a CPA license</strong>.',
      ],
    ];
    $table[] = [
      'billing_class' => [
        '#type' => 'item',
        '#markup' => '52',
      ],
      'description' => [
        '#type' => 'item',
        '#markup' => 'Employment Status <strong>Leave of Absence or Part-time, Retired, Seasonal, Unemployed</strong>.',
      ],
    ];
    $table[] = [
      'billing_class' => [
        '#type' => 'item',
        '#markup' => '54',
      ],
      'description' => [
        '#type' => 'item',
        '#markup' => 'Employment Status <strong>Working in Accounting</strong>.',
      ],
    ];
    return [
      '#type' => 'item',
      '#markup' => '<h3>Billing Classes FY 2017</h3>',
      'table' => $table,
    ];
  }

}
