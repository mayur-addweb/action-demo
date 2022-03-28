<?php

namespace Drupal\am_net_membership\BillingClass;

/**
 * Defines common constants for Billing class code logic.
 */
interface BillingClassCodeInterface {

  /**
   * Licensed In: State Only.
   */
  const LICENSED_IN_STATE_ONLY = 'in_state_only';
  /**
   * Licensed In: Out-Of-State Only.
   */
  const LICENSED_IN_OUT_OF_STAT_ONLY = 'out_of_stat_only';

  /**
   * Licensed In: In and Out-Of-State.
   */
  const LICENSED_IN_AND_OUT_OF_STATE = 'in_and_out_of_state';

  /**
   * AM.net Not Certified.
   */
  const AM_NET_NOT_CERTIFIED = 'N';

  /**
   * AM.net Certified.
   */
  const AM_NET_CERTIFIED = 'Y';

  /**
   * AM.net Certified: Out-Of-State Only.
   */
  const AM_NET_CERTIFIED_OUT_OF_STAT_ONLY = 'O';

  /**
   * AM.net Certified: In State Only.
   */
  const AM_NET_CERTIFIED_IN_STATE_ONLY = 'I';

  /**
   * AM.net Certified: In and Out-Of-State.
   */
  const AM_NET_CERTIFIED_IN_AND_OUT_OF_STATE = 'B';

  /**
   * Membership Selection: I am a licensed CPA = Mbr: Fellow Member.
   */
  const MEMBERSHIP_SELECTION_LICENSED_CPA = 'MF';

  /**
   * Membership Selection: Unlicensed Professional = Mbr: Associate Member.
   */
  const MEMBERSHIP_SELECTION_UNLICENSED_PROFESSIONAL = 'MA';

  /**
   * Membership Selection: I am an college student = Mbr: College Student.
   */
  const MEMBERSHIP_SELECTION_COLLEGE_STUDENT = 'MC';

  /**
   * Employment Status: Leave of Absence.
   */
  const EMPLOYMENT_STATUS_LEAVE_OF_ABSENCE = '95';

  /**
   * Employment Status: Part-time.
   */
  const EMPLOYMENT_STATUS_PART_TIME = '96';

  /**
   * Employment Status: Seasonal.
   */
  const EMPLOYMENT_STATUS_SEASONAL = '98';

  /**
   * Employment Status: Unemployed.
   */
  const EMPLOYMENT_STATUS_UNEMPLOYED = '99';

  /**
   * Employment Status: Retired.
   */
  const EMPLOYMENT_STATUS_RETIRED = '97';

  /**
   * Employment Status: Full-time.
   */
  const EMPLOYMENT_STATUS_FULL_TIME = '94';

  /**
   * Membership Qualify: Firm Administrator.
   */
  const MEMBERSHIP_QUALIFY_FIRM_ADMINISTRATOR = 'FA';

  /**
   * Membership Qualify: Employed in an accounting position.
   */
  const MEMBERSHIP_QUALIFY_EMPLOYED_IN_AN_ACCOUNTING_POSITION = 'AP';

  /**
   * Membership Qualify: Employed by a CPA.
   */
  const MEMBERSHIP_QUALIFY_EMPLOYED_BY_A_CPA = 'EB';

  /**
   * Membership Qualify: Non-CPA owner of CPA firm.
   */
  const MEMBERSHIP_QUALIFY_NON_CPA_OWNER_OF_CPA_FIRM = 'NO';

  /**
   * Membership Qualify: Pursuing a CPA license.
   */
  const MEMBERSHIP_QUALIFY_PURSUING_CERTIFIED_PUBLIC_ACCOUNTANT_LICENSE = 'PU';

  /**
   * Job Position: Educator.
   */
  const JOB_POSITION_EDUCATOR = '74';

  /**
   * Mail Preference Home.
   */
  const GENERAL_MAIL_PREFERENCE_CODE_HOME = 'H';

  /**
   * Mail Preference Work.
   */
  const GENERAL_MAIL_PREFERENCE_CODE_OFFICE = 'O';

  /**
   * Rol Id: Firm Administrator.
   */
  const ROL_FIRM_ADMINISTRATOR = 'firm_administrator';

}
